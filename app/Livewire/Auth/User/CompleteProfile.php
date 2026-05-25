<?php

namespace App\Livewire\Auth\User;

use App\Enums\GenderState;
use App\Enums\StatusState;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Mary\Traits\Toast;

class CompleteProfile extends Component
{
    use Toast, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?string $googleAvatar = null;

    public $avatarFile = null;

    public string $username = '';

    public $phone = null;

    public string $date_of_birth_en = '';

    public string $date_of_birth_np = '';

    public string $gender = '';

    public ?string $bio = null;

    public $password = '';

    public $confirm_password = '';

    //    public bool $is_seller = false;

    public $genderStates = [];

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('home'), navigate: true);
        }

        $this->genderStates = backedEnumAsArray(GenderState::cases());

        $googleUser = session('google_user');
        $verified = session('otp_verified');
        if (! $googleUser || ! $verified) {
            $this->redirect(route('user.login'), navigate: true);

            return;
        }
        $this->name = $googleUser['name'] ?? '';
        $this->email = $googleUser['email'] ?? '';
        $this->googleAvatar = $googleUser['avatar'] ?? null;
        $this->phone = $googleUser['phone'] ?? '';
        $this->username = str($googleUser['name'] ?? '')->lower()->replace(' ', '_')->toString();
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric|digits:10',
            'date_of_birth_en' => 'required|date|before:today',
            'gender' => 'required',
            'bio' => 'nullable|string|max:500',
            'avatarFile' => 'nullable|image|max:2048',
            'password' => [
                'required',
                'min:8',
                'regex:/[A-Z]/',       // at least 1 uppercase
                'regex:/[a-z]/',       // at least 1 lowercase
                'regex:/[0-9]/',       // at least 1 number
                'regex:/[@$!%*#?&]/',  // at least 1 special character
                'regex:/^\S+$/',       // no spaces
                'different:email',
                'different:username',
            ],
            'confirm_password' => 'required|same:password',
        ],
            [
                'phone.required' => 'Phone Number is required',
                'phone.numeric' => 'Invalid Phone Number',
                'phone.digits' => 'Invalid Phone Number',
                'date_of_birth_en.required' => 'Date of Birth is required',
                'date_of_birth_en.date' => 'Invalid Date of Birth',
                'date_of_birth_en.before' => 'Invalid Date of Birth',
                'gender.required' => 'Gender is required',
                'gender.in' => 'Invalid Gender',
                'bio.required' => 'Bio is required',
                'bio.string' => 'Bio must be a string',
                'bio.max' => 'Bio must be at most 500 characters',
                'avatarFile.required' => 'Avatar is required',
                'avatarFile.image' => 'Avatar must be an image',
                'avatarFile.max' => 'Avatar must be at most 2MB',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'confirm_password.required' => 'Confirm Password is required',
                'confirm_password.min' => 'Confirm Password must be at least 8 characters',
                'confirm_password.same' => 'Confirm Password must be the same as Password',
            ]);

        $avatarPath = null;

        if ($this->avatarFile) {
            $avatarPath = $this->avatarFile->store('avatars', 'public');
        } elseif ($this->googleAvatar) {
            try {
                $response = Http::withOptions(['verify' => false])->get($this->googleAvatar);
                if ($response->successful()) {
                    $fileName = 'avatars/'.Str::uuid().'.jpg';
                    Storage::disk('public')->put($fileName, $response->body());
                    $avatarPath = $fileName;
                }
            } catch (\Exception $e) {
                $avatarPath = $this->googleAvatar;
            }
        }

        $hashPassword = Hash::make($this->password);
        $googleUser = session('google_user');

        $user = User::updateOrCreate(
            ['email' => $this->email],
            [
                'google_id' => $googleUser['google_id'] ?? null,
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'phone' => $this->phone ?: null,
                'date_of_birth_en' => $this->date_of_birth_en ?: null,
                'daate_of_birth_np' => $this->date_of_birth_np ?: null,
                'gender' => $this->gender ?: null,
                'password' => $hashPassword,
                'is_verified' => true,
                'avatar' => $avatarPath,
                'bio' => $this->bio ?: null,
                'is_seller' => false,
                'seller_application_pending' => false,
                'is_auction_allowed' => false,
                'status' => StatusState::ACTIVE->value,
            ]
        );

        //        if ($this->is_seller === true) {
        //            $admins = Admin::get();
        //            foreach ($admins as $admin) {
        //                $admin->notify(new SellerRegisteredNotification($user));
        //            }
        //        }

        session()->forget(['google_user', 'otp_verified']);

        Auth::login($user, remember: true);

        $this->success('Profile completed successfully! Welcome to Sajha Auction.', position: 'toast-bottom');

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.complete-profile');
    }
}
