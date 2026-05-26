<div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6">
    <div class="mx-auto w-full max-w-7xl space-y-6">
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl">
            <div class="bg-gradient-to-r from-[#1F6F5F] to-[#2FA084] p-8 text-white">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                        <x-icon name="o-hand-raised" class="h-7 w-7" />
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-2xl font-bold">Join Auction Access</h1>
                        <p class="max-w-3xl text-sm text-white/85">
                            Add document rows, upload multiple images, and save all changes together. You can edit existing entries and replace every image at once before submitting.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-b border-gray-100 bg-gray-50 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-500">Application Status</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $statusLabel }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $statusDescription }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-500">Access Type</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Auction Bidding</p>
                    <p class="mt-1 text-sm text-gray-600">Approval is required before placing bids.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-500">Rows Allowed</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Up to 10 images</p>
                    <p class="mt-1 text-sm text-gray-600">Add more rows if you need extra document pages.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-500">Image Limit</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">2MB per image</p>
                    <p class="mt-1 text-sm text-gray-600">Use clear front and back photos for faster review.</p>
                </div>
            </div>

            <form wire:submit="submitApplication" class="space-y-6 p-6 sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Document Table</h2>
                        <p class="text-sm text-gray-600">
                            Each row is one database record. Existing rows can be updated, and new rows will be inserted when you click join.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="addDocumentRow"
                        @disabled(! $canSubmit || count($documentRows) >= 10)
                        class="inline-flex items-center justify-center rounded-xl border border-[#1F6F5F] px-4 py-2.5 text-sm font-semibold text-[#1F6F5F] transition hover:bg-[#1F6F5F] hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Add Another Image
                    </button>
                </div>

                @error('documentRows')
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ $message }}</div>
                @enderror

                <div class="overflow-hidden rounded-2xl border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Document Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Current Image</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Upload / Replace</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($documentRows as $index => $row)
                                    <tr wire:key="document-row-{{ $row['id'] ?? 'new' }}-{{ $index }}">
                                        <td class="px-4 py-4 align-top text-sm font-semibold text-gray-700">
                                            {{ $index + 1 }}
                                        </td>

                                        <td class="px-4 py-4 align-top">
                                            <select
                                                wire:model="documentRows.{{ $index }}.type"
                                                @disabled(! $canSubmit)
                                                class="w-full min-w-44 rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 outline-none transition focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 disabled:cursor-not-allowed disabled:bg-gray-100"
                                            >
                                                @foreach ($documentTypes as $documentType)
                                                    <option value="{{ $documentType['value'] }}">{{ $documentType['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @error("documentRows.$index.type")
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <td class="px-4 py-4 align-top">
                                            @if ($row['image'])
                                                <img
                                                    src="{{ $row['image']->temporaryUrl() }}"
                                                    alt="Selected document preview"
                                                    class="h-24 w-24 rounded-xl border border-gray-200 object-cover"
                                                >
                                            @elseif ($row['image_path'])
                                                <img
                                                    src="{{ asset('storage/' . $row['image_path']) }}"
                                                    alt="Existing document image"
                                                    class="h-24 w-24 rounded-xl border border-gray-200 object-cover"
                                                >
                                            @else
                                                <div class="flex h-24 w-24 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-400">
                                                    No image
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="file"
                                                wire:model="documentRows.{{ $index }}.image"
                                                @disabled(! $canSubmit)
                                                accept="image/*"
                                                class="block w-full min-w-56 rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-[#1F6F5F] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#18584c] disabled:cursor-not-allowed disabled:bg-gray-100"
                                            >

                                            @if ($row['id'] !== null)
                                                <p class="mt-2 text-xs text-gray-500">Select a file only if you want to replace the current image.</p>
                                            @endif

                                            @error("documentRows.$index.image")
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <td class="px-4 py-4 align-top">
                                            <button
                                                type="button"
                                                wire:click="removeDocumentRow({{ $index }})"
                                                @disabled(! $canSubmit)
                                                class="inline-flex items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div wire:loading wire:target="documentRows.*.image" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Uploading selected image...
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        @disabled(! $canSubmit)
                        class="cursor-pointer inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    >
                        Join Auction
                    </button>

                    <a
                        href="{{ route('home') }}"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 sm:w-auto"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
