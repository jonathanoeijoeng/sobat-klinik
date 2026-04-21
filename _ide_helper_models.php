<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic query()
 */
	class Clinic extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name_en
 * @property string|null $name_id
 * @property bool $is_active
 * @property string|null $version
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 search($term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereNameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Icd10 whereVersion($value)
 */
	class Icd10 extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $outpatient_visit_id
 * @property string $invoice_number
 * @property numeric $registration_fee
 * @property numeric $practitioner_fee
 * @property numeric $medicine_total
 * @property numeric $grand_total
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $xendit_external_id
 * @property string|null $xendit_invoice_url
 * @property string|null $paid_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\OutpatientVisit $outpatient_visit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereMedicineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereOutpatientVisitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePractitionerFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereRegistrationFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereXenditExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereXenditInvoiceUrl($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $satusehat_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereSatusehatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereUpdatedAt($value)
 */
	class Location extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $kfa_code
 * @property string $name
 * @property string $display_name
 * @property string|null $form_type
 * @property string|null $manufacturer
 * @property numeric $fix_price
 * @property numeric $het_price
 * @property string|null $satusehat_medication_id
 * @property string|null $last_synced_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Prescription> $prescriptions
 * @property-read int|null $prescriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereFixPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereFormType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereHetPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereKfaCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereLastSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereSatusehatMedicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereUpdatedAt($value)
 */
	class Medicine extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $satusehat_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSatusehatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 */
	class Organization extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $outpatient_visit_id
 * @property string $icd10_code
 * @property string $icd10_display
 * @property bool $is_primary
 * @property string|null $satusehat_condition_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Icd10|null $icd10
 * @property-read \App\Models\OutpatientVisit $outpatient_visit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereIcd10Code($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereIcd10Display($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereOutpatientVisitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereSatusehatConditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutPatientDiagnosis whereUpdatedAt($value)
 */
	class OutPatientDiagnosis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $visit_number
 * @property int $patient_id
 * @property int $practitioner_id
 * @property int $location_id
 * @property string $status
 * @property string $internal_status
 * @property string|null $satusehat_encounter_id
 * @property string|null $complaint
 * @property \Carbon\CarbonImmutable|null $arrived_at
 * @property string|null $in_progress_at
 * @property string|null $finished_at
 * @property string|null $cancelled_at
 * @property string|null $at_practitioner_at
 * @property string|null $sent_to_pharmacy_at
 * @property string|null $sent_for_payment_at
 * @property string|null $paid_at
 * @property string|null $dispensed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OutPatientDiagnosis> $diagnoses
 * @property-read int|null $diagnoses_count
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Location|null $location
 * @property-read \App\Models\Patient|null $patient
 * @property-read \App\Models\Practitioner|null $practitioner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Prescription> $prescriptions
 * @property-read int|null $prescriptions_count
 * @property-read \App\Models\VitalSign|null $vitalSign
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit pendingSync()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereArrivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereAtPractitionerAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereComplaint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereDispensedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereInProgressAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereInternalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit wherePendingSync()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit wherePractitionerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereSatusehatEncounterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereSentForPaymentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereSentToPharmacyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutpatientVisit whereVisitNumber($value)
 */
	class OutpatientVisit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $satusehat_id
 * @property string $nik
 * @property string $medical_record_number
 * @property string $name
 * @property string $gender
 * @property string $birth_date
 * @property string|null $phone_number
 * @property string|null $address
 * @property string|null $province_code
 * @property string|null $city_code
 * @property string|null $district_code
 * @property string|null $village_code
 * @property string|null $last_sync_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $age
 * @property-read mixed $age_string
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereCityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereDistrictCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereLastSyncAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereMedicalRecordNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereProvinceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereSatusehatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereVillageCode($value)
 */
	class Patient extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nik
 * @property string $name
 * @property string|null $satusehat_id
 * @property string|null $ihs_number
 * @property string|null $sip
 * @property string $profession_type
 * @property bool $is_active
 * @property numeric $fee
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereIhsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereProfessionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereSatusehatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereSip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Practitioner whereUpdatedAt($value)
 */
	class Practitioner extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $outpatient_visit_id
 * @property int $medicine_id
 * @property string $medicine_name
 * @property string|null $instruction
 * @property int $qty_ordered
 * @property int $qty_dispensed
 * @property string $uom
 * @property string $status
 * @property string|null $satusehat_medication_request_id
 * @property string|null $satusehat_medication_dispense_id
 * @property string|null $sent_to_pharmacy_at
 * @property string|null $sent_for_payment_at
 * @property string|null $paid_at
 * @property string|null $dispensed_at
 * @property string|null $external_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Medicine $medicine
 * @property-read \App\Models\OutpatientVisit $outpatient_visit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereDispensedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereExternalAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereInstruction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereMedicineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereMedicineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereOutpatientVisitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereQtyDispensed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereQtyOrdered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereSatusehatMedicationDispenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereSatusehatMedicationRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereSentForPaymentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereSentToPharmacyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereUpdatedAt($value)
 */
	class Prescription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $outpatient_visit_id
 * @property int|null $systole
 * @property int|null $diastole
 * @property string|null $satusehat_observation_blood_pressure_id
 * @property numeric|null $weight
 * @property string|null $satusehat_observation_weight_id
 * @property int|null $height
 * @property string|null $satusehat_observation_height_id
 * @property numeric|null $temperature
 * @property string|null $satusehat_observation_temperature_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\OutpatientVisit $outpatient_visit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereDiastole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereOutpatientVisitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereSatusehatObservationBloodPressureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereSatusehatObservationHeightId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereSatusehatObservationTemperatureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereSatusehatObservationWeightId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereSystole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VitalSign whereWeight($value)
 */
	class VitalSign extends \Eloquent {}
}

