<?php

use Livewire\Component;
use App\Models\Patient;
use App\Services\SatuSehatService;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $showModal = false;
    public $nik, $name, $birth_date, $satusehat_id, $phone_number, $email, $address;
    public $gender;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function newPatient()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['nik', 'name', 'birth_date', 'gender', 'satusehat_id', 'phone_number', 'email', 'address']);
    }

    public function save()
    {
        $this->validate([
            'nik' => 'required|digits:16|unique:patients,nik',
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required',
        ]);

        Patient::create([
            'nik' => $this->nik,
            'clinic_id' => Auth::user()->clinic_id,
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'satusehat_id' => $this->satusehat_id,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
        ]);

        $this->dispatch('toast', text: 'Data berhasil disimpan!', type: 'success');
        $this->closeModal();
    }

    public function findPatientByNik()
    {
        $this->validate(['nik' => 'required|digits:16']);

        $service = app(SatuSehatService::class);
        $result = $service->getPatientByNik($this->nik);

        if ($result['success']) {
            // Isi otomatis field di form
            $this->name = $result['name'];
            $this->birth_date = $result['birth_date'];
            $this->gender = $result['gender'];
            $this->satusehat_id = $result['satusehat_id'];

            $this->dispatch('toast', text: 'Data ditemukan!', type: 'success');
        } else {
            $this->dispatch('toast', text: $result['message'], type: 'error');
        }
    }

    public function render()
    {
        $patients = Patient::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%')->orWhere('nik', 'ilike', '%' . $this->search . '%');
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(25);

        return $this->view([
            'patients' => $patients,
        ]);
    }
};
?>

<div>
    <x-header header="Daftar Pasien"
        description="Kelola data demografi pasien secara terpusat dengan integrasi NIK Nasional. Modul ini memastikan setiap pasien memiliki profil yang valid dan terhubung dengan <b>SatuSehat Patient ID (Logical ID)</b> guna menjamin konsistensi data rekam medis lintas fasilitas kesehatan." />
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4">
        <x-input wire:model.live.debounce.100ms="search" name="search" placeholder="Cari pasien..."
            class="mb-4 md:max-w-lg w-full" />
        <x-button wire:click="newPatient" class="mb-4" color="brand">Registrasi Baru</x-button>
    </div>
    <div class="border rounded-lg overflow-x-scroll shadow-sm md:mx-0 md:px-0">
        <table class="w-full md:min-w-full divide-y divide-gray-200 ">
            <thead class="bg-brand-500">
                <tr>
                    <th
                        class="w-auto whitespace-nowrap px-4 md:px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama / NIK
                    </th>
                    <th
                        class="w-px whitespace-nowrap px-4 md:px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest hidden md:table-cell">
                        Status
                        SATUSEHAT</th>
                    <th
                        class="w-px whitespace-nowrap px-4 md:px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Phone</th>
                    <th
                        class="w-px whitespace-nowrap px-4 md:px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest hidden md:table-cell">
                        L/P</th>
                    <th class="px-4 md:px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">
                        Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($patients as $patient)
                    <tr>
                        <td class="w-auto md:w-px px-4 md:px-6 py-4">
                            <div class="flex items-start">
                                <div class="text-sm font-medium text-gray-900">{{ $patient->name }}</div>
                                <img src="/logo/satusehat.png" alt="avatar"
                                    class="w-3 h-3 rounded-full object-cover mt-1 ml-1 block md:hidden">
                            </div>
                            <div class="text-xs text-gray-500">{{ $patient->nik }}</div>
                            <div class="text-xs text-gray-500 flex items-center md:hidden">
                                @if ($patient->gender === 'male')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="blue"
                                        class="h-5 w-5 mr-1">
                                        <path
                                            d="M15.0491 8.53666L18.5858 5H14V3H22V11H20V6.41421L16.4633 9.95088C17.4274 11.2127 18 12.7895 18 14.5C18 18.6421 14.6421 22 10.5 22C6.35786 22 3 18.6421 3 14.5C3 10.3579 6.35786 7 10.5 7C12.2105 7 13.7873 7.57264 15.0491 8.53666ZM10.5 20C13.5376 20 16 17.5376 16 14.5C16 11.4624 13.5376 9 10.5 9C7.46243 9 5 11.4624 5 14.5C5 17.5376 7.46243 20 10.5 20Z">
                                        </path>
                                    </svg>
                                @elseif ($patient->gender === 'female')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red"
                                        class="h-5 w-5 mr-1 rotate-45">
                                        <path
                                            d="M11 15.9339C7.33064 15.445 4.5 12.3031 4.5 8.5C4.5 4.35786 7.85786 1 12 1C16.1421 1 19.5 4.35786 19.5 8.5C19.5 12.3031 16.6694 15.445 13 15.9339V18H18V20H13V24H11V20H6V18H11V15.9339ZM12 14C15.0376 14 17.5 11.5376 17.5 8.5C17.5 5.46243 15.0376 3 12 3C8.96243 3 6.5 5.46243 6.5 8.5C6.5 11.5376 8.96243 14 12 14Z">
                                        </path>
                                    </svg>
                                @endif
                            </div>
                        </td>
                        <td class="text-sm w-auto whitespace-nowrap px-4 md:px-6 py-4 hidden md:table-cell">
                            @if ($patient->satusehat_patient_id)
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Terintegrasi ({{ $patient->satusehat_patient_id }})
                                </span>
                            @else
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Sync
                                </span>
                            @endif
                        </td>
                        <td class="w-px whitespace-nowrap px-4 md:px-6 py-4 text-center text-sm font-medium ">
                            {{ $patient->phone_number }}</td>
                        <td
                            class="w-px whitespace-nowrap md:px-4 px-6 py-4 text-center text-sm font-medium hidden md:table-cell">
                            {{ $patient->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                        <td class="px-4 md:px-12 py-4 text-right text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900">Detail</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $patients->links() }}
    </div>
    <div x-data="{ isOpen: @entangle('showModal') }" x-show="isOpen" @keydown.escape.window="isOpen = false"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div
                class="relative bg-white dark:bg-zinc-800 w-full max-w-3xl rounded-2xl shadow-xl overflow-hidden transform transition-all">

                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold dark:text-white uppercase tracking-widest">
                            Pendaftaran Pasien Baru - {{ $satusehat_id }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-6 gap-4">
                        <div class="col-span-3">
                            <x-input name="nik" wire:model="nik" class="w-full" placeholder="NIK" />
                        </div>
                        <x-button variant="brand" wire:click="findPatientByNik" loading="nik">
                            Cari Data
                        </x-button>
                        @if ($satusehat_id)
                            <div class="w-full transition-all duration-1000 col-span-2">
                                <x-verified-badge text="SatuSehat Verified!" />
                            </div>
                        @endif
                    </div>
                    <div class="mt-8 font-bold">Detail Pasien</div>
                    <div class="mt-2 grid grid-cols-5 gap-3">
                        <div class="col-span-4">
                            <x-input name="name" wire:model="name" class="w-full" placeholder="Nama" />
                        </div>
                        <x-select name="gender" wire:model="gender" class="w-full" placeholder="Jenis Kelamin">
                            <option value="">Jenis Kelamin</option>
                            <option value="male">Pria</option>
                            <option value="female">Wanita</option>
                        </x-select>

                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <x-input name="birth_date" wire:model="birth_date" class="w-full" placeholder="Tanggal Lahir"
                            type="date" />
                        <x-input name="phone_number" wire:model="phone_number" class="w-full"
                            placeholder="Nomor Telepon" type="tel" />
                        <x-input name="email" wire:model="email" class="w-full" placeholder="Email" type="email" />
                    </div>
                    <div class="mt-4">
                        <x-input name="address" wire:model="address" class="w-full" placeholder="Alamat" />
                    </div>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-zinc-800/50 flex justify-end gap-3">
                    <x-button variant="zinc" type="submit" wire:click="closeModal">
                        Cancel
                    </x-button>
                    <x-button variant="brand" wire:click="save" loading="save" :disabled="!$satusehat_id">
                        Save
                    </x-button>
                </div>
            </div>


        </div>
    </div>
</div>
</div>
