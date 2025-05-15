# Báo cáo lỗi trong hệ thống test

## Lỗi đã phát hiện

### 1. Lỗi trong DoctorModel

#### 1.1. Phương thức `getDateTimeFormat()`

- **Mô tả lỗi**: Phương thức `getDateTimeFormat()` trả về `' h:i A'` thay vì `null` khi không có cột 'data'
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/models/DoctorModel.php`
- **Dòng**: 257-267
- **Tác động**: Test `testGetDateTimeFormat` trong `DoctorModelTest.php` thất bại
- **Giải pháp đề xuất**: Sửa phương thức `getDateTimeFormat()` để trả về `null` khi không có cột 'data' hoặc `preferences.dateformat` không tồn tại

#### 1.2. Phương thức `isEmailVerified()`

- **Mô tả lỗi**: Phương thức `isEmailVerified()` trả về `true` thay vì `false` khi không có cột 'data'
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/models/DoctorModel.php`
- **Dòng**: 274-285
- **Tác động**: Test `testEmailMethods` trong `DoctorModelTest.php` thất bại
- **Giải pháp đề xuất**: Sửa phương thức `isEmailVerified()` để trả về `false` khi không có cột 'data' hoặc `data.email_verification_hash` không tồn tại

#### 1.3. Phương thức `select()`

- **Mô tả lỗi**: Phương thức `select()` không thể tìm kiếm theo số điện thoại
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/models/DoctorModel.php`
- **Dòng**: 40-76
- **Tác động**: Test `DOC_FIND_03.2` trong `DoctorModelTest.php` chỉ thành công khi sử dụng workaround
- **Giải pháp đề xuất**: Sửa phương thức `select()` để có thể tìm kiếm theo số điện thoại với các định dạng khác nhau

#### 1.4. Phương thức `sendVerificationEmail()`

- **Mô tả lỗi**: Phương thức `sendVerificationEmail()` gọi hàm `readableRandomString()` nhưng hàm này không được định nghĩa
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/models/DoctorModel.php`
- **Dòng**: 330
- **Tác động**: Fatal Error khi chạy test `testEmailMethods` trong `DoctorModelTest.php`
- **Giải pháp đề xuất**: Định nghĩa hàm `readableRandomString()` hoặc sử dụng một hàm khác để tạo chuỗi ngẫu nhiên

### 2. Lỗi trong PatientModel

#### 2.1. Phương thức `select()`

- **Mô tả lỗi**: Phương thức `select()` không thể tìm kiếm theo số điện thoại
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/models/PatientModel.php`
- **Tác động**: Test `PT_SEL_04` trong `PatientModelTest.php` thất bại
- **Giải pháp đề xuất**: Sửa phương thức `select()` để có thể tìm kiếm theo số điện thoại với các định dạng khác nhau

### 3. Lỗi trong BookingPhotosController

#### 3.1. Phương thức `upload()` không tồn tại

- **Mô tả lỗi**: Phương thức `upload()` được gọi trong `BookingPhotosController.php` nhưng không được định nghĩa
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/controllers/BookingPhotosController.php`
- **Dòng**: 37
- **Tác động**: Fatal Error khi chạy test cho `BookingPhotosControllerTest.php`
- **Giải pháp đề xuất**: Định nghĩa phương thức `upload()` trong `BookingPhotosController.php`

### 4. Lỗi "Too many connections"

- **Mô tả lỗi**: Khi chạy tất cả các test cùng lúc, chúng ta gặp lỗi "Too many connections" vì có quá nhiều kết nối đến cơ sở dữ liệu
- **File**: `/Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/app/tests/DatabaseTestCase.php`
- **Tác động**: Không thể chạy tất cả các test cùng lúc
- **Giải pháp đề xuất**: Sửa file `DatabaseTestCase.php` để đóng kết nối sau khi hoàn thành mỗi test hoặc sử dụng một kết nối duy nhất cho tất cả các test

## Cải tiến đã thực hiện

### 1. Xóa file DBMock.php không cần thiết

- **Mô tả**: File DBMock.php gây ra lỗi "Cannot redeclare class DB"
- **Tác động**: Đã xóa file này và sửa file bootstrap.php để không require file này nữa
- **Kết quả**: Có thể chạy test trực tiếp bằng PHPUnit mà không cần thông qua script run-coverage.php

### 2. Cải thiện script run-coverage.php

- **Mô tả**: Đã cải thiện script run-coverage.php để có thể chạy test cho một nhóm file cụ thể và tạo báo cáo độ phủ tốt hơn
- **Tác động**: Có thể chạy test cho từng nhóm file riêng biệt (models, controllers) và tạo báo cáo độ phủ cho các test thành công
- **Kết quả**: Báo cáo độ phủ đầy đủ hơn và dễ dàng chạy test cho từng nhóm file

### 3. Thêm phương thức upload() tạm thời vào BookingPhotosController.php

- **Mô tả**: Đã thêm phương thức upload() tạm thời vào BookingPhotosController.php để tránh Fatal Error khi chạy test
- **Tác động**: Có thể chạy test cho BookingPhotosControllerTest.php mà không gặp Fatal Error
- **Kết quả**: Có thể chạy tất cả các test mà không gặp lỗi dừng đột ngột

## Hướng dẫn sử dụng script run-coverage.php

```bash
# Chạy tất cả các test
php run-coverage.php

# Chạy test cho file cụ thể
php run-coverage.php tests/models/DoctorModelTest.php

# Chạy tất cả các test trong thư mục models
php run-coverage.php models

# Chạy tất cả các test trong thư mục controllers
php run-coverage.php controllers

# Hiển thị hướng dẫn sử dụng
php run-coverage.php help
```

## Kết luận

Hệ thống test hiện tại có một số lỗi cần được sửa chữa để đảm bảo độ chính xác của các test. Các lỗi chính tập trung vào DoctorModel, PatientModel và BookingPhotosController. Ngoài ra, vấn đề "Too many connections" cũng cần được giải quyết để có thể chạy tất cả các test cùng lúc.

Các cải tiến đã thực hiện giúp cải thiện quy trình test và tạo báo cáo độ phủ tốt hơn. Tuy nhiên, các lỗi trong code vẫn cần được sửa chữa bởi developer để đảm bảo tất cả các test đều thành công.
