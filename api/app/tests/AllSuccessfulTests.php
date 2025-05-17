<?php
/**
 * Test suite to run all successful tests
 */
class AllSuccessfulTests extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new self();

        // Add all successful test files
        $suite->addTestFile(__DIR__ . '/controllers/AppointmentControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/AppointmentQueueControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/AppointmentQueueNowControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/AppointmentRecordControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/AppointmentsControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/DoctorControllerTest.php');
        $suite->addTestFile(__DIR__ . '/controllers/DoctorsControllerTest.php');
        $suite->addTestFile(__DIR__ . '/DatabaseConnectionTest.php');
        $suite->addTestFile(__DIR__ . '/models/AppointmentModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/AppointmentRecordModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/AppointmentRecordsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/AppointmentsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/BookingModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/BookingPhotoModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/BookingPhotosModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/BookingsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/ClinicModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/ClinicsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/DoctorsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/DrugModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/DrugsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/NotificationModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/NotificationsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/PatientModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/PatientsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/RoomModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/RoomsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/ServiceModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/ServicesModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/SpecialitiesModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/SpecialityModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/TreatmentModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/TreatmentsModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/DoctorAndServiceModelTest.php');
        $suite->addTestFile(__DIR__ . '/models/DoctorModelTest.php');

        return $suite;
    }
}
