<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Doctor_Schedules;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppointmentService
{
    public function getAllDoctorsForSecretary(): array
    {
        return Doctor::with(['user', 'specialization'])
            ->orderBy('id')
            ->where('is_active', true)
            ->get()
            ->map(function (Doctor $doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name,
                    'specialization_name' => $doctor->specialization?->name,
                ];
            })
            ->values()
            ->all();
    }

    public function getActiveDoctorsBySpecialization(int $specializationId): array
    {
        return Doctor::with('user')
            ->where('specialization_id', $specializationId)
            ->where('is_active', true)
            ->get()
            ->map(function (Doctor $doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name,
                ];
            })
            ->values()
            ->all();
    }

    public function getAvailableSlotsForDoctorId(int $doctorId, int $daysToCheck = 10): array
    {
        $doctor = Doctor::where('id', $doctorId)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->getAvailableSlotsForDoctor($doctor, $daysToCheck);
    }

    public function getSecretaryAppointmentsByStatus(
        string $status,
        ?string $date = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 500
    ): array {
        $query = Appointment::with(['patient.user', 'doctor.user'])
            ->where('status', $status)
            ->orderBy('appointment_date');

        if ($date) { 
            $query->whereDate('appointment_date', $date);
        } else {
            if ($fromDate) {
                $query->whereDate('appointment_date', '>=', $fromDate);
            } else {
                $query->whereDate('appointment_date', '>=', Carbon::today());
            }

            if ($toDate) {
                $query->whereDate('appointment_date', '<=', $toDate);
            }
        }

        return $query->limit($limit)->get()->map(function (Appointment $appointment) {
            return [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient?->user?->name,
                'patient_phone' => $appointment->patient?->user?->phone_number,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor?->user?->name,
                'day' => $appointment->day,
                'appointment_date' => optional($appointment->appointment_date)->toDateTimeString(),
                'status' => $appointment->status,
            ];
        })->values()->all();
    }

    public function resolveDoctorForSpecialization(int $specializationId, ?int $doctorId = null): Doctor
    {
        $query = Doctor::where('specialization_id', $specializationId)
            ->where('is_active', true);

        if ($doctorId) {
            $query->where('id', $doctorId);
        }

        return $query->firstOrFail();
    }

    public function getAvailableSlotsForDoctor(Doctor $doctor, int $daysToCheck = 10): array
    {
        $result = [];

        for ($i = 0; $i < $daysToCheck; $i++) {
            $date = Carbon::today()->addDays($i);
            $day = $this->normalizeDay($date);

            $schedules = Doctor_Schedules::where('doctor_id', $doctor->id)
                ->where('day', $day)
                ->get();

            $slots = [];

            foreach ($schedules as $schedule) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);

                while ($start < $end) {
                    $slots[] = $start->format('H:i');
                    $start->addMinutes(30);
                }
            }

            if ($i === 0) {
                $now = Carbon::now();
                $slots = array_filter($slots, function ($slot) use ($now, $date) {
                    $slotDateTime = Carbon::parse($date->toDateString() . ' ' . $slot);
                    return $slotDateTime->gt($now);
                });
            }

            $booked = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $date)
                ->where('status', '!=', 'cancelled')
                ->pluck('appointment_date')
                ->map(fn($t) => Carbon::parse($t)->format('H:i'))
                ->toArray();

            $available = array_values(array_diff($slots, $booked));

            $result[] = [
                'date' => $date->toDateString(),
                'day' => $day,
                'slots' => $available,
            ];
        }

        return $result;
    }

    public function bookPatientAppointment(int $patientId, array $data): Appointment
    {
        $doctor = $this->resolveDoctorForSpecialization(
            $data['specialization_id'],
            $data['doctor_id'] ?? null
        );

        $appointmentDateTime = Carbon::parse($data['date'] . ' ' . $data['time']);

        $this->ensureSlotAvailable($doctor, $appointmentDateTime);

        return $this->createAppointment($patientId, $doctor->id, $appointmentDateTime, 'scheduled');
    }

    public function bookSecretaryAppointment(array $data): Appointment
    {
        $doctor = Doctor::where('id', $data['doctor_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $patient = $this->findOrCreatePatient($data['patient_name'], $data['phone_number'] ?? null);
        $appointmentDateTime = Carbon::parse($data['date'] . ' ' . $data['time']);

        $this->ensureSlotAvailable($doctor, $appointmentDateTime);

        return $this->createAppointment($patient->id, $doctor->id, $appointmentDateTime, 'confirmed');
    }

    public function confirmAppointmentBySecretary(int $appointmentId): Appointment
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->status !== 'scheduled') {
            throw new \Exception('لا يمكن تأكيد هذا الموعد');
        }

        $appointment->status = 'confirmed';
        $appointment->save();

        return $appointment;
    }

    private function ensureSlotAvailable(Doctor $doctor, Carbon $appointmentDateTime): void
    {
        if ($appointmentDateTime->lt(Carbon::now())) {
            throw new \Exception('لا يمكن حجز موعد في الماضي');
        }

        $day = $this->normalizeDay($appointmentDateTime);

        $schedules = Doctor_Schedules::where('doctor_id', $doctor->id)
            ->where('day', $day)
            ->get();

        if ($schedules->isEmpty()) {
            throw new \Exception('الدكتور لا يعمل بهذا اليوم');
        }

        $isInSchedule = false;

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($appointmentDateTime->toDateString() . ' ' . $schedule->start_time);
            $end = Carbon::parse($appointmentDateTime->toDateString() . ' ' . $schedule->end_time);

            if ($appointmentDateTime->lt($start) || $appointmentDateTime->gte($end)) {
                continue;
            }

            $minutesDiff = $start->diffInMinutes($appointmentDateTime);
            if ($minutesDiff % 30 === 0) {
                $isInSchedule = true;
                break;
            }
        }

        if (!$isInSchedule) {
            throw new \Exception('الوقت المختار غير متاح ضمن دوام الطبيب');
        }

        $exists = Appointment::where('doctor_id', $doctor->id)
            ->where('appointment_date', $appointmentDateTime)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            throw new \Exception('هذا الموعد محجوز مسبقاً');
        }
    }

    private function createAppointment(int $patientId, int $doctorId, Carbon $appointmentDateTime, string $status): Appointment
    {
        return Appointment::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'day' => $this->normalizeDay($appointmentDateTime),
            'appointment_date' => $appointmentDateTime,
            'status' => $status,
        ]);
    }

    private function normalizeDay(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SUNDAY => 'sun',
            Carbon::MONDAY => 'mon',
            Carbon::TUESDAY => 'tues',
            Carbon::WEDNESDAY => 'wed',
            Carbon::THURSDAY => 'thy',
            Carbon::FRIDAY => 'fri',
            Carbon::SATURDAY => 'sat',
        };
    }

    private function findOrCreatePatient(string $patientName, ?string $phoneNumber = null): Patient
    {
        if (!$phoneNumber) {
            throw new \Exception('رقم الهاتف مطلوب لإنشاء ملف مريض جديد');
        }

        $user = null;

        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            $user = User::create([
                'name' => $patientName,
                'phone_number' => $phoneNumber,
                'password' => Hash::make('11111111'),
                'is_verified' => true,
            ]);

            $user->assignRole('patient');
        }

        if ($user->patient) {
            return $user->patient;
        }

        return Patient::create([
            'user_id' => $user->id,
        ]);
    }
}
