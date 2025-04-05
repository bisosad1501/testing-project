<?php
/**
 * Define database credentials
 */
define("DB_HOST", "localhost"); 
define("DB_NAME", "doantotnghiep"); 
define("DB_USER", "root"); 
define("DB_PASS", "root"); 
define("DB_ENCODING", "utf8"); 

/**
 * Define DB tables
 * (Các hằng số dưới đây chỉ định nghĩa nếu chúng chưa được định nghĩa để tránh lỗi duplicate)
 */
if (!defined("TABLE_PREFIX")) define("TABLE_PREFIX", "tn_");
if (!defined("TABLE_SPECIALITIES")) define("TABLE_SPECIALITIES", "specialities");
if (!defined("TABLE_DOCTORS")) define("TABLE_DOCTORS", "doctors");
if (!defined("TABLE_BOOKINGS")) define("TABLE_BOOKINGS", "booking");
if (!defined("TABLE_APPOINTMENTS")) define("TABLE_APPOINTMENTS", "appointments");
if (!defined("TABLE_PATIENTS")) define("TABLE_PATIENTS", "patients");
if (!defined("TABLE_TREATMENTS")) define("TABLE_TREATMENTS", "treatments");
if (!defined("TABLE_APPOINTMENT_RECORDS")) define("TABLE_APPOINTMENT_RECORDS", "appointment_records");
if (!defined("TABLE_SERVICES")) define("TABLE_SERVICES", "services");
if (!defined("TABLE_DOCTOR_AND_SERVICE")) define("TABLE_DOCTOR_AND_SERVICE", "doctor_and_service");
if (!defined("TABLE_NOTIFICATIONS")) define("TABLE_NOTIFICATIONS", "notifications");
if (!defined("TABLE_ROOMS")) define("TABLE_ROOMS", "rooms");
if (!defined("TABLE_BOOKING_PHOTOS")) define("TABLE_BOOKING_PHOTOS", "booking_photo");
if (!defined("TABLE_DRUGS")) define("TABLE_DRUGS", "drugs");
if (!defined("TABLE_CLINICS")) define("TABLE_CLINICS", "clinics");
?>