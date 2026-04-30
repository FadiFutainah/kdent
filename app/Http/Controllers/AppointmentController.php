<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AppointmentService;

class AppointmentController extends Controller
{
	public function __construct(private AppointmentService $service)
	{
	}

	public function bookBySecretary(Request $request)
	{
		$data = $request->validate([
			'patient_name' => 'required|string|min:2|max:255',
			'phone_number' => 'required|string|min:10',
			'doctor_id' => 'required|exists:doctors,id',
			'date' => 'required|date',
			'time' => 'required',
		]);

		$appointment = $this->service->bookSecretaryAppointment($data);

		return response()->json($appointment);
	}

	public function listAllDoctorsForSecretary()
	{
		$doctors = $this->service->getAllDoctorsForSecretary();

		return response()->json($doctors);
	}

	public function listDoctorsBySpecialization(Request $request)
	{
		$data = $request->validate([
			'specialization_id' => 'required|exists:specializations,id',
		]);

		$doctors = $this->service->getActiveDoctorsBySpecialization((int) $data['specialization_id']);

		return response()->json($doctors);
	}

	public function availableSlotsByDoctor(int $doctorId, Request $request)
	{
		$data = $request->validate([
			'days' => 'nullable|integer|min:1|max:30',
		]);

		$days = (int) ($data['days'] ?? 10);
		$slots = $this->service->getAvailableSlotsForDoctorId($doctorId, $days);

		return response()->json($slots);
	}

	public function listScheduledBySecretary(Request $request)
	{
		$data = $request->validate([
			'date' => 'required|date',
			'limit' => 'nullable|integer|min:1|max:500',
		]);

		$appointments = $this->service->getSecretaryAppointmentsByStatus(
			'scheduled',
			$data['date'],
			null,
			null,
			(int) ($data['limit'] ?? 500)
		);

		return response()->json($appointments);
	}

	public function listConfirmedBySecretary(Request $request)
	{
		$data = $request->validate([
			'date' => 'required|date',
			'limit' => 'nullable|integer|min:1|max:500',
		]);

		$appointments = $this->service->getSecretaryAppointmentsByStatus(
			'confirmed',
			$data['date'],
			null,
			null,
			(int) ($data['limit'] ?? 200)
		);

		return response()->json($appointments);
	}

	public function confirmBySecretary(int $appointmentId)
	{
		$appointment = $this->service->confirmAppointmentBySecretary($appointmentId);

		return response()->json($appointment);
	}
}
