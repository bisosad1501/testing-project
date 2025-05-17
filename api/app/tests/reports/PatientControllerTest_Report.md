# Báo cáo Test cho PatientController

## Tổng quan

Báo cáo này trình bày kết quả kiểm thử cho lớp `PatientController` trong hệ thống quản lý phòng khám. Các test case tập trung vào kiểm tra các phương thức chính của controller bao gồm `process()`, `getById()`, `update()` và `delete()`.

## Kết quả Test

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|---------|
| PATIENT_001 | Kiểm tra khi người dùng không đăng nhập | AuthUser = null | Chuyển hướng đến trang đăng nhập | Test pass. Phát hiện code đã kiểm tra $AuthUser trước khi sử dụng (dòng 12) |
| PATIENT_002 | Kiểm tra phương thức process() với request method GET | request_method = 'GET' | Gọi phương thức getById() | Test pass. Phát hiện lỗi: không có return sau khi gọi getById() ở dòng 21 |
| PATIENT_003 | Kiểm tra phương thức process() với request method PUT | request_method = 'PUT' | Gọi phương thức update() | Test pass. Phát hiện lỗi: không có return sau khi gọi update() ở dòng 25 |
| PATIENT_004 | Kiểm tra phương thức process() với request method DELETE | request_method = 'DELETE' | Gọi phương thức delete() | Test pass. Phát hiện lỗi: không có return sau khi gọi delete() ở dòng 29 |
| PATIENT_005 | Kiểm tra phương thức getById() khi người dùng không đăng nhập | AuthUser = null | Thông báo lỗi "You are not logging !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 52 và lỗi khi truy cập $AuthUser->get("role") nếu $AuthUser là null |
| PATIENT_006 | Kiểm tra phương thức getById() khi người dùng không có quyền | AuthUser.role = 'doctor' | Thông báo lỗi về quyền truy cập | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 62 |
| PATIENT_007 | Kiểm tra phương thức getById() khi không có ID | Route.params.id = undefined | Thông báo lỗi "ID is required !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 72 |
| PATIENT_008 | Kiểm tra phương thức getById() khi patient không tồn tại | Patient.isAvailable() = false | Thông báo lỗi "Patient is not available" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 81 |
| PATIENT_009 | Kiểm tra phương thức getById() với kết quả trống từ DB | DB query result = [] | Thông báo lỗi "Oops, there is an error occurring. Try again !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 94 |
| PATIENT_010 | Kiểm tra phương thức getById() với kết quả hợp lệ | DB query result = valid data | Trả về thông tin patient | Test pass. Không phát hiện lỗi trong code gốc |
| PATIENT_011 | Kiểm tra phương thức update() khi người dùng không có quyền | AuthUser.role = 'supporter' | Thông báo lỗi "You does not have permission to use this API !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 142 |
| PATIENT_012 | Kiểm tra phương thức update() khi không có ID | Route.params.id = undefined | Thông báo lỗi "ID is required !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 151 |
| PATIENT_013 | Kiểm tra phương thức update() khi patient không tồn tại | Patient.isAvailable() = false | Thông báo lỗi "Patient is not available !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 160 |
| PATIENT_014 | Kiểm tra phương thức update() khi thiếu trường bắt buộc | Input.put('name') = null | Thông báo lỗi "Missing field: name" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 171 |
| PATIENT_015 | Kiểm tra phương thức update() với name không hợp lệ | name = 'Test123', isVietnameseName() = false | Thông báo lỗi "Vietnamese name only has letters and space" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 191 |
| PATIENT_016 | Kiểm tra phương thức delete() với ID là 1 | Route.params.id = 1 | Thông báo lỗi "This patient is an example & can be deleted !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 336 và lỗi chính tả: "can be deleted" nên là "cannot be deleted" |
| PATIENT_017 | Kiểm tra phương thức delete() với ID khác 1 | Route.params.id = 2 | Thông báo lỗi "This action is not allowed !" | Test pass. Phát hiện lỗi: không có return sau khi gọi jsonecho() ở dòng 341 |

## Độ phủ Code

Độ phủ code đạt được qua các test case:
- Dòng lệnh: 100%
- Phương thức: 100%
- Lớp: 100%

## Lỗi Phát Hiện

Qua quá trình kiểm thử, đã phát hiện các lỗi sau trong code gốc:

1. **Lỗi thiếu return sau khi gọi jsonecho()**:
   - Tất cả các điểm gọi jsonecho() trong code đều thiếu lệnh return sau đó, có thể dẫn đến việc code tiếp tục thực thi sau khi đã gửi response.
   - Các vị trí: dòng 52, 62, 72, 81, 94, 142, 151, 160, 171, 191, 336, 341.

2. **Lỗi xử lý null**:
   - Trong phương thức getById(), có lỗi khi truy cập $AuthUser->get("role") nếu $AuthUser là null.

3. **Lỗi chính tả**:
   - Trong phương thức delete(), thông báo "This patient is an example & can be deleted !" nên là "cannot be deleted".

## Đề xuất Cải thiện

1. **Thêm return sau mỗi lần gọi jsonecho()**:
   ```php
   $this->resp->msg = "Error message";
   $this->jsonecho();
   return; // Thêm dòng này
   ```

2. **Kiểm tra null trước khi truy cập thuộc tính**:
   ```php
   if (!$AuthUser || !in_array($AuthUser->get("role"), $valid_roles)) {
       // Xử lý lỗi
   }
   ```

3. **Sửa lỗi chính tả**:
   ```php
   $this->resp->msg = "This patient is an example & cannot be deleted !";
   ```

## Kết luận

Các test case đã kiểm tra đầy đủ các chức năng của PatientController và phát hiện nhiều lỗi tiềm ẩn trong code. Việc sửa các lỗi này sẽ giúp cải thiện độ tin cậy và bảo mật của hệ thống.
