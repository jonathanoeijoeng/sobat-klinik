<?php

use Livewire\Component;
use App\Models\Patient;
use App\Services\SatuSehatService;

new class extends Component {
    public string $search = '';
    public $showModal = false;
    public $nik, $name, $birth_date, $satusehat_id, $phone_number, $email, $address;
    public $gender;

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
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'satusehat_id' => $this->satusehat_id,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
        ]);

        $this->dispatch('toast', message: 'Data berhasil disimpan!', type: 'success');
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

            $this->dispatch('notify', message: 'Data ditemukan!', type: 'success');
        } else {
            $this->dispatch('notify', message: $result['message'], type: 'error');
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
    <x-header header="Daftar Pasien" description="List pasien yang sudah terdaftar di sistem" />
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <x-input wire:model.live.debounce.100ms="search" name="search" placeholder="Cari pasien..."
            class="mb-4 md:max-w-lg w-full" />
        <x-button wire:click="newPatient" class="mb-4" color="brand">Registrasi Baru</x-button>
    </div>
    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama / NIK
                    </th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Status
                        SATUSEHAT</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Phone</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        L/P</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($patients as $patient)
                    <tr>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $patient->nik }}</div>
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            @if ($patient->satusehat_id)
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Terintegrasi ({{ $patient->satusehat_id }})
                                </span>
                            @else
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Sync
                                </span>
                            @endif
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                            {{ $patient->phone_number }}</td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                            {{ $patient->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                        <td class="px-12 py-4 text-right text-sm font-medium">
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
