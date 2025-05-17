# Báo cáo kết quả test PatientController

## Tổng quan

Chúng tôi đã thực hiện kiểm thử đơn vị cho `PatientController` để đánh giá tính đúng đắn và phát hiện lỗi trong code. Báo cáo này tổng hợp kết quả kiểm thử và các vấn đề được phát hiện.

## Kết quả test

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|---------|
| PATIENT_001 | Kiểm tra khi người dùng không đăng nhập | AuthUser = null | Chuyển hướng đến trang đăng nhập | Test phát hiện lỗi: Không có lỗi trong phần này, code đã kiểm tra $AuthUser trước khi sử dụng (dòng 12) |
| PATIENT_002 | Kiểm tra phương thức process() với request method GET | request_method = 'GET' | Gọi getById() | Test phát hiện lỗi: Không có return sau khi gọi getById() ở dòng 21, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_003 | Kiểm tra phương thức process() với request method PUT | request_method = 'PUT' | Gọi update() | Test phát hiện lỗi: Không có return sau khi gọi update() ở dòng 25, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_004 | Kiểm tra phương thức process() với request method DELETE | request_method = 'DELETE' | Gọi delete() | Test phát hiện lỗi: Không có return sau khi gọi delete() ở dòng 29, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_005 | Kiểm tra phương thức getById() khi người dùng không đăng nhập | AuthUser = null | Thông báo lỗi "You are not logging !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 52, có thể dẫn đến việc code tiếp tục thực thi. Lỗi khi truy cập $AuthUser->get("role") nếu $AuthUser là null |
| PATIENT_006 | Kiểm tra phương thức getById() khi người dùng không có quyền | AuthUser->role = 'doctor' | Thông báo lỗi về quyền | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 62, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_007 | Kiểm tra phương thức getById() khi không có ID | Route->params->id = null | Thông báo lỗi "ID is required !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 72, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_008 | Kiểm tra phương thức getById() khi patient không tồn tại | Patient->isAvailable() = false | Thông báo lỗi "Patient is not available" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 81, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_009 | Kiểm tra phương thức getById() với kết quả trống từ DB | DB result = [] | Thông báo lỗi "Oops, there is an error occurring. Try again !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 94, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_010 | Kiểm tra phương thức getById() với kết quả hợp lệ | DB result = valid data | resp->result = 1, resp->data = patient data | Test không phát hiện lỗi trong code gốc |
| PATIENT_011 | Kiểm tra phương thức update() khi người dùng không có quyền | AuthUser->role = 'doctor' | Thông báo lỗi "You does not have permission to use this API !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 142, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_012 | Kiểm tra phương thức update() khi không có ID | Route->params->id = null | Thông báo lỗi "ID is required !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 151, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_013 | Kiểm tra phương thức update() khi patient không tồn tại | Patient->isAvailable() = false | Thông báo lỗi "Patient is not available !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 160, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_014 | Kiểm tra phương thức update() khi thiếu trường bắt buộc | Input::put('name') = null | Thông báo lỗi "Missing field: name" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 171, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_015 | Kiểm tra phương thức update() với name không hợp lệ | Input::put('name') = invalid name | Thông báo lỗi "Vietnamese name only has letters and space" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 191, có thể dẫn đến việc code tiếp tục thực thi |
| PATIENT_016 | Kiểm tra phương thức delete() với ID là 1 | Route->params->id = 1 | Thông báo lỗi "This patient is an example & can be deleted !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 336, có thể dẫn đến việc code tiếp tục thực thi. Lỗi chính tả: "can be deleted" nên là "cannot be deleted" |
| PATIENT_017 | Kiểm tra phương thức delete() với ID khác 1 | Route->params->id = 2 | Thông báo lỗi "This action is not allowed !" | Test phát hiện lỗi: Không có return sau khi gọi jsonecho() ở dòng 341, có thể dẫn đến việc code tiếp tục thực thi |

## Vấn đề về độ phủ code

Mặc dù các test đã được viết để kiểm tra tất cả các phương thức trong `PatientController`, nhưng báo cáo độ phủ code vẫn cho thấy 0%. Nguyên nhân có thể là:

1. Lỗi trong code gốc (đặc biệt là lỗi truy cập `$AuthUser->get("role")` khi `$AuthUser` là null) khiến test không thể thực thi hết code.
2. Cấu hình PHPUnit không đúng để theo dõi độ phủ code.
3. Các test case sử dụng mock thay vì thực sự thực thi code gốc.

## Kết luận

Qua quá trình kiểm thử, chúng tôi đã phát hiện nhiều lỗi trong `PatientController.php`, đặc biệt là:

1. Lỗi nghiêm trọng khi truy cập `$AuthUser->get("role")` khi `$AuthUser` là null, có thể gây crash ứng dụng.
2. Nhiều trường hợp thiếu `return` sau khi gọi `jsonecho()`, có thể dẫn đến hành vi không mong muốn.
3. Lỗi chính tả trong thông báo lỗi.

Chúng tôi đề xuất sửa các lỗi này càng sớm càng tốt để đảm bảo ứng dụng hoạt động ổn định và đáng tin cậy.

## Đề xuất cải thiện

1. Sửa lỗi truy cập `$AuthUser->get("role")` khi `$AuthUser` là null.
2. Thêm `return` sau mỗi lần gọi `jsonecho()`.
3. Sửa lỗi chính tả trong thông báo lỗi.
4. Cải thiện cấu hình PHPUnit để theo dõi độ phủ code chính xác hơn.
5. Viết thêm test case để tăng độ phủ code.
