<?php

use Livewire\Component;
use App\Models\Clinic;
use Livewire\WithPagination;
use Carbon\Carbon;
use Livewire\WithFileUploads;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $clinic_id;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $logo;
    public $new_logo; // Temporary file upload
    public $satusehat_organization_id;
    public $satusehat_client_id;
    public $satusehat_client_secret;
    public $active_until;

    public function edit($id)
    {
        $this->showModal = true;
        $clinic = Clinic::find($id);
        $this->clinic_id = $clinic->id;
        $this->name = $clinic->name;
        $this->email = $clinic->email;
        $this->phone = $clinic->phone;
        $this->address = $clinic->address;
        $this->logo = $clinic->logo;
        $this->satusehat_organization_id = $clinic->satusehat_organization_id;
        $this->satusehat_client_id = $clinic->satusehat_client_id;
        $this->satusehat_client_secret = $clinic->satusehat_client_secret;
        $this->active_until = Carbon::parse($clinic->active_until)->format('Y-m-d');
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function deleteExistingLogo()
    {
        if ($this->logo) {
            // 1. Hapus file fisiknya dari folder storage/app/public/logo
            if (Storage::disk('public')->exists('logo/' . $this->logo)) {
                Storage::disk('public')->delete('logo/' . $this->logo);
            }

            // 2. Update database: set logo jadi null
            $clinic = Clinic::find($this->clinic_id);
            $clinic->update([
                'logo' => null,
            ]);

            // 3. Reset state di Livewire agar UI otomatis berubah
            $this->logo = null;
            $this->new_logo = null;

            // Optional: kasih notifikasi
            $this->dispatch('toast', type: 'success', text: 'Logo berhasil dihapus');
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'satusehat_organization_id' => 'required',
            'satusehat_client_id' => 'required',
            'satusehat_client_secret' => 'required',
            'new_logo' => 'nullable|image|max:2048',
        ]);

        $clinic = Clinic::find($this->clinic_id);
        if ($this->new_logo) {
            // Simpan file ke storage/app/public/logos
            $path = $this->new_logo->store('logo', 'public');
            $fileNameOnly = $this->new_logo->hashName();

            // Update path di database
            $clinic = Clinic::find($this->clinic_id);
            $clinic->update([
                'logo' => $fileNameOnly,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'satusehat_organization_id' => $this->satusehat_organization_id,
                'satusehat_client_id' => $this->satusehat_client_id,
                'satusehat_client_secret' => $this->satusehat_client_secret,
                'active_until' => $this->active_until,
            ]);
        }

        $this->closeModal();
        $this->reset(['name', 'email', 'phone', 'address', 'logo', 'new_logo', 'satusehat_organization_id', 'satusehat_client_id', 'satusehat_client_secret', 'active_until']);
    }

    public function render()
    {
        $clinics = Clinic::query()->orderBy('name', 'asc')->paginate(25);

        return $this->view([
            'clinics' => $clinics,
        ]);
    }
};
?>

<div>
    <x-header header="Master Data Klinik"
        description="Pusat pengaturan identitas klinik, lokalisasi format keuangan, dan manajemen akses integrasi SatuSehat Kemenkes untuk menjamin legalitas operasional rekam medis." />

    <x-button wire:click="newClinic" class="mb-4" color="brand">Registrasi Baru</x-button>

    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama Klinik
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Alamat</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Phone</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        SatuSehat Org ID</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        SatuSehat client ID</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        SatuSehat client secret</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($clinics as $clinic)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10">
                                    @if ($clinic->logo)
                                        <img class="w-10 h-10 rounded-full object-cover border border-gray-200"
                                            src="{{ asset('storage/logo/' . $clinic->logo) }}"
                                            alt="{{ $clinic->name }}">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold">
                                            {{ substr($clinic->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <div class="font-medium text-gray-900">{{ $clinic->name }}</div>
                                    <div class="text-xs text-gray-500 uppercase">{{ $clinic->initial }}</div>
                                </div>
                            </div>
                        </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium capitalize">
                            {{ $clinic->address }}</td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $clinic->phone }}</td>
                        <td class="px-6 py-4 text-center text-sm font-medium font-mono">
                            {{ $clinic->satusehat_organization_id ? substr($clinic->satusehat_organization_id, 0, 6) . '*****' : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium font-mono">
                            {{ $clinic->satusehat_client_id ? substr($clinic->satusehat_client_id, 0, 6) . '*****' : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium font-mono">
                            {{ $clinic->satusehat_client_secret ? substr($clinic->satusehat_client_secret, 0, 6) . '*****' : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-center gap-3">
                                <x-edit wire:click="edit({{ $clinic->id }})" />
                                <x-delete wire:click="delete({{ $clinic->id }})" />
                            </div>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $clinics->links() }}
    </div>

    <div x-data="{ open: @entangle('showModal') }" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 dark:bg-gray-800">
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Klinik</h3>
            </div>

            <div class="space-y-4 mt-4">
                <div class="grid grid-cols-5 gap-4">
                    <div class="col-span-3">
                        <x-input wire:model="name" name="name" label="Nama Klinik" class="mb-4" />
                        <x-input wire:model="email" name="email" label="Email" class="mb-4" />
                        <x-input wire:model="phone" name="phone" label="Phone" class="mb-4" />
                        <x-input wire:model="active_until" name="active_until" type="date" label="Active Until"
                            class="mb-4" />
                    </div>
                    <div class="col-span-2 space-y-4">
                        <div class="flex flex-col items-center">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo
                                Klinik</label>

                            <div class="relative group">
                                @if ($new_logo)
                                    <img src="{{ $new_logo->temporaryUrl() }}"
                                        class="w-64 h-64 object-cover rounded-lg border shadow-sm">
                                @elseif ($logo)
                                    <img src="{{ asset('storage/logo/' . $logo) }}"
                                        class="w-64 h-64 object-cover rounded-lg border shadow-sm">
                                    <button type="button" wire:click="deleteExistingLogo"
                                        wire:confirm="Hapus logo ini secara permanen?"
                                        class="absolute -top-3 -right-3 bg-red-600 text-white rounded-full p-1.5 shadow-lg hover:bg-red-700 transition-all cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @else
                                    <div
                                        class="w-64 h-64 bg-gray-100 dark:bg-gray-700 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="mt-2 text-sm text-gray-500">Belum ada logo</span>
                                    </div>
                                @endif

                                <div wire:loading wire:target="new_logo"
                                    class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-lg">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                </div>
                            </div>

                            <div class="mt-4 w-3/4">
                                <input type="file" wire:model="new_logo" id="upload_{{ $clinic_id }}"
                                    class="hidden">
                                <label for="upload_{{ $clinic_id }}"
                                    class="cursor-pointer w-full inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">
                                    Pilih File Logo
                                </label>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-2 text-center">Format: JPG, PNG, WEBP (Maks. 2MB)
                            </p>
                        </div>
                    </div>
                </div>


                <x-input wire:model="address" name="address" label="Alamat" class="mb-4" />

                <x-input wire:model="satusehat_organization_id" name="satusehat_organization_id"
                    label="SatuSehat Org ID" class="mb-4" />
                <x-input wire:model="satusehat_client_id" name="satusehat_client_id" label="SatuSehat Client ID"
                    class="mb-4" />
                <x-input wire:model="satusehat_client_secret" name="satusehat_client_secret"
                    label="SatuSehat Client Secret" class="mb-4" />
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="open = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Batal
                </button>
                <button wire:click="save" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Update
                </button>
            </div>
        </div>
    </div>

</div>
