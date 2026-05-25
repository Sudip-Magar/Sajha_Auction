<?php

namespace App\Livewire\User;

use App\Enums\DocumentImageType;
use App\Models\Admin;
use App\Models\DocumentImage;
use App\Notifications\AuctionApplicationSubmittedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class JoinAuction extends Component
{
    use Toast, WithFileUploads;

    /** @var array<int, array{value:string,label:string}> */
    public array $documentTypes = [];
    /** @var array<int, array{id:int|null,type:string,image_path:string|null,image:mixed}> */
    public array $documentRows = [];
    /** @var array<int> */
    public array $removedDocumentIds = [];
    public bool $isAuctionAllowed = false;
    public string $applicationStatus = 'not_submitted';
    public bool $canSubmit = true;
    public string $statusLabel = 'Not Submitted';
    public string $statusDescription = 'Upload your identification documents to request auction access.';

    public function getListeners(): array
    {
        $userId = Auth::id();

        if (!$userId) {
            return [
                'userNotificationReceived' => '$refresh',
            ];
        }

        return [
            'userNotificationReceived' => '$refresh',
            "echo-notification:App.Models.User.{$userId}" => '$refresh',
        ];
    }

    public function mount(): void
    {
        if (Auth::guard('web')->user()->is_auction_allowed) {
            $this->success('You already have an auction access.');
            $this->redirect(url()->previous(), navigate: true);
        }
        $this->documentTypes = collect(DocumentImageType::cases())
            ->map(fn(DocumentImageType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();

        $this->loadApplicationState();
    }

    private function loadApplicationState(): void
    {
        $user = Auth::user();
        $documents = $user->documentImages()->latest()->get();

        $this->isAuctionAllowed = (bool)$user->is_auction_allowed;
        $this->documentRows = $documents
            ->map(fn(DocumentImage $document): array => [
                'id' => $document->id,
                'type' => $document->type->value,
                'image_path' => $document->image,
                'image' => null,
            ])
            ->all();

        if ($this->documentRows === []) {
            $this->documentRows[] = $this->emptyDocumentRow();
        }

        if ($this->isAuctionAllowed || $documents->contains('is_approved', true)) {
            $this->applicationStatus = 'approved';
            $this->statusLabel = 'Approved';
            $this->statusDescription = 'Your account is approved for live auction bidding.';
            $this->canSubmit = false;

            return;
        }

        $hasPendingDocuments = $documents->contains(
            fn(DocumentImage $document): bool => !$document->is_approved && !$document->is_rejected
        );

        if ($hasPendingDocuments) {
            $this->applicationStatus = 'pending';
            $this->statusLabel = 'Pending Approval';
            $this->statusDescription = 'Your uploaded documents are under review. You can still upload more images while the request is pending.';
            $this->canSubmit = true;

            return;
        }

        if ($documents->contains('is_rejected', true)) {
            $this->applicationStatus = 'rejected';
            $this->statusLabel = 'Rejected';
            $this->statusDescription = 'Your previous request was rejected. Review your files and submit clearer document images.';
            $this->canSubmit = true;

            return;
        }

        $this->applicationStatus = 'not_submitted';
        $this->statusLabel = 'Not Submitted';
        $this->statusDescription = 'Upload your identification documents to request auction access.';
        $this->canSubmit = true;
    }

    /**
     * @return array{id:null,type:string,image_path:null,image:null}
     */
    private function emptyDocumentRow(): array
    {
        return [
            'id' => null,
            'type' => DocumentImageType::CITIZENSHIPFRONT->value,
            'image_path' => null,
            'image' => null,
        ];
    }

    public function addDocumentRow(): void
    {
        $this->documentRows[] = $this->emptyDocumentRow();
    }

    public function removeDocumentRow(int $index): void
    {
        if (!array_key_exists($index, $this->documentRows)) {
            return;
        }

        $documentId = $this->documentRows[$index]['id'];
        if ($documentId !== null) {
            $this->removedDocumentIds[] = $documentId;
            $this->removedDocumentIds = array_values(array_unique($this->removedDocumentIds));
        }

        unset($this->documentRows[$index]);
        $this->documentRows = array_values($this->documentRows);

        if ($this->documentRows === []) {
            $this->documentRows[] = $this->emptyDocumentRow();
        }
    }

    public function submitApplication(): void
    {
        if (!$this->canSubmit) {
            $this->warning('Your current auction access request cannot be updated right now.');

            return;
        }

        $this->validate([
            'documentRows' => ['required', 'array', 'min:1', 'max:10'],
            'documentRows.*.type' => ['required', 'string'],
            'documentRows.*.image' => ['nullable', 'image', 'max:2048'],
        ], [
            'documentRows.required' => 'Please add at least one document row.',
            'documentRows.min' => 'Please add at least one document row.',
            'documentRows.max' => 'You can manage up to 10 document images at once.',
            'documentRows.*.type.required' => 'Please choose a document type for each row.',
            'documentRows.*.image.image' => 'Each uploaded file must be a valid image.',
            'documentRows.*.image.max' => 'Each image must be 2MB or smaller.',
        ]);

        foreach ($this->documentRows as $index => $row) {
            if (DocumentImageType::tryFrom($row['type']) === null) {
                $this->addError("documentRows.{$index}.type", 'Please choose a valid document type.');
            }

            if ($row['id'] === null && !$row['image']) {
                $this->addError("documentRows.{$index}.image", 'Please upload an image for each new row.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $user = Auth::user();

        if ($this->removedDocumentIds !== []) {
            $documentsToDelete = $user->documentImages()
                ->whereIn('id', $this->removedDocumentIds)
                ->get();

            foreach ($documentsToDelete as $document) {
                Storage::disk('public')->delete($document->image);
                $document->delete();
            }
        }

        foreach ($this->documentRows as $row) {
            if ($row['id'] !== null) {
                $document = $user->documentImages()->find($row['id']);
                if (!$document) {
                    continue;
                }

                $payload = [
                    'type' => $row['type'],
                ];

                if ($row['image']) {
                    Storage::disk('public')->delete($document->image);
                    $payload['image'] = $row['image']->store('document-images', 'public');
                    $payload['is_approved'] = false;
                    $payload['is_rejected'] = false;
                }

                $document->update($payload);

                continue;
            }

            $user->documentImages()->create([
                'type' => $row['type'],
                'image' => $row['image']->store('document-images', 'public'),
            ]);
        }

        $this->removedDocumentIds = [];
        $this->loadApplicationState();

        if ($this->applicationStatus === 'pending') {
            Admin::query()->each(function (Admin $admin) use ($user): void {
                $admin->notify(new AuctionApplicationSubmittedNotification($user));
            });
        }

        $this->success('Your auction application submitted successfully.');
        $this->redirect(url()->previous(), navigate: true);
    }

    public function render(): View
    {
        $this->loadApplicationState();

        return view('livewire.user.join-auction');
    }

    private function resolveDocumentStatus(DocumentImage $document): string
    {
        if ($document->is_approved) {
            return 'Approved';
        }

        if ($document->is_rejected) {
            return 'Rejected';
        }

        return 'Pending';
    }
}
