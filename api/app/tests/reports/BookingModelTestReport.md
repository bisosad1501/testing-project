# Báo Cáo Kiểm Thử BookingModel

## 1. Tổng quan
Báo cáo này mô tả chi tiết việc kiểm thử đơn vị (unit test) cho lớp BookingModel trong hệ thống quản lý phòng khám. Mục tiêu của việc kiểm thử là đảm bảo tất cả các phương thức trong BookingModel hoạt động chính xác và xử lý các trường hợp đặc biệt một cách phù hợp.

## 2. Quy trình kiểm thử

### 2.1 Công cụ và thư viện sử dụng
- **PHPUnit 5.7.27**: Framework kiểm thử cho PHP
- **PHP 5.6.40**: Phiên bản PHP dùng để chạy test
- **MySQL**: Hệ quản trị cơ sở dữ liệu
- **Pixie**: Thư viện query builder hỗ trợ thao tác với database
- **Viocon**: Container hỗ trợ dependency injection

### 2.2 Các function/class/file được test
- **File được test**: `app/models/BookingModel.php`
- **Các phương thức được test**:
  - **__construct()**: Kiểm tra khởi tạo đối tượng
  - **select()**: Kiểm tra chọn đặt lịch theo ID
  - **extendDefaults()**: Kiểm tra thiết lập giá trị mặc định
  - **insert()**: Kiểm tra thêm đặt lịch mới
  - **update()**: Kiểm tra cập nhật thông tin đặt lịch
  - **delete()**: Kiểm tra xóa đặt lịch

**Lý do không test các thành phần khác**: BookingModel là một model đơn giản chỉ chứa các phương thức CRUD cơ bản và không có logic phức tạp khác cần test. Các thành phần liên quan đến giao diện người dùng sẽ được test riêng trong các test khác.

### 2.3 Bảng bộ test case

| Tên file/class | Mã test case | Mục tiêu của test | Input dữ liệu | Expected output | Ghi chú |
|----------------|--------------|-------------------|---------------|-----------------|---------|
| BookingModel.php | TC-BM-01 | Kiểm tra tạo mới đặt lịch | Dữ liệu đặt lịch mẫu | Đặt lịch được tạo thành công với ID > 0 | Kiểm tra cả trong DB |
| BookingModel.php | TC-BM-02 | Kiểm tra đọc thông tin đặt lịch theo ID | ID đặt lịch vừa tạo | Đặt lịch được tìm thấy và dữ liệu đúng | Kiểm tra tất cả các trường |
| BookingModel.php | TC-BM-03 | Kiểm tra cập nhật thông tin đặt lịch | Dữ liệu đặt lịch mới | Đặt lịch được cập nhật thành công | Kiểm tra dữ liệu sau khi cập nhật |
| BookingModel.php | TC-BM-04 | Kiểm tra xóa đặt lịch | ID đặt lịch | Đặt lịch được xóa thành công | Kiểm tra cả trong DB |
| BookingModel.php | TC-BM-05 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| BookingModel.php | TC-BM-06 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra giá trị mặc định |
| BookingModel.php | TC-BM-07 | Kiểm tra update đặt lịch không tồn tại | Model không khả dụng | Hàm update trả về false | Kiểm tra xử lý lỗi |
| BookingModel.php | TC-BM-08 | Kiểm tra delete đặt lịch không tồn tại | Model không khả dụng | Hàm delete trả về false | Kiểm tra xử lý lỗi |
| BookingModel.php | TC-BM-09 | Kiểm tra insert đặt lịch đã tồn tại | Model đã khả dụng | Hàm insert trả về false | Kiểm tra xử lý trùng lặp |

### 2.4 Link GitHub
(Phần này sẽ được bổ sung sau)

### 2.5 Báo cáo kết quả chạy test

```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

Runtime:       PHP 5.6.40
Configuration: /Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/phpunit.xml.dist


==================================================
🔍 TC-BM: Kiểm tra quy trình CRUD
==================================================

📋 TC-BM-01: Tạo mới đặt lịch
  Expected: Đặt lịch được tạo thành công với ID > 0
  Result: Booking ID: 99
  Status: ✅ SUCCESS

📋 TC-BM-02: Đọc thông tin đặt lịch theo ID
  Expected: Đặt lịch được tìm thấy và có dữ liệu đúng
  Result: ID: 99 - Tìm thấy: Có, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-BM-03: Cập nhật thông tin đặt lịch
  Expected: Đặt lịch được cập nhật thành công
  Result: Cập nhật thành công, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-BM-04: Xóa đặt lịch
  Expected: Đặt lịch được xóa thành công
  Result: Xóa thành công, Kiểm tra tồn tại: Đã xóa, Kiểm tra DB: Đã xóa khỏi DB
  Status: ✅ SUCCESS

==================================================
🔍 TC-BM-05: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm kiếm đặt lịch với ID không tồn tại
  Expected: Đặt lịch không được tìm thấy
  Result: ID không tồn tại: 1098, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BM-06: Kiểm tra phương thức extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập đúng giá trị mặc định
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BM-07: Kiểm tra update đặt lịch không tồn tại
==================================================

📋 Cập nhật thông tin đặt lịch không tồn tại
  Expected: Hàm update trả về false
  Result: Update đặt lịch không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BM-08: Kiểm tra delete đặt lịch không tồn tại
==================================================

📋 Xóa đặt lịch không tồn tại
  Expected: Hàm delete trả về false
  Result: Delete đặt lịch không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BM-09: Kiểm tra insert đặt lịch đã tồn tại
==================================================

📋 Thực hiện insert trên model đã khả dụng
  Expected: Hàm insert trả về false
  Result: Insert đặt lịch đã tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

Time: 58 ms, Memory: 5.00MB

OK (6 tests, 19 assertions)
```

**Kết quả tổng hợp:**
- Tổng số test case: 9
- Tổng số test functions: 6
- Tổng số assertions: 19
- Kết quả: Tất cả PASS (100%)
- Thời gian thực thi: 58ms
- Bộ nhớ sử dụng: 5.00MB

### 2.6 Báo cáo độ phủ code
Báo cáo độ phủ code sẽ được bổ sung sau khi hoàn thành test toàn bộ dự án.

## 3. Kết luận và đề xuất
- **Kết luận**: BookingModel đã được kiểm thử đầy đủ và tất cả các chức năng hoạt động đúng như mong đợi. Cả các trường hợp thông thường và các trường hợp đặc biệt đều được xử lý phù hợp.
- **Đề xuất**: 
  - Cần bổ sung kiểm tra ràng buộc với bảng DoctorModel, PatientModel, và ServiceModel
  - Thêm logic kiểm tra tính hợp lệ của appointment_date và appointment_time
  - Cân nhắc thêm cơ chế lưu lịch sử thay đổi trạng thái đặt lịch
  - Bổ sung cơ chế tìm kiếm đặt lịch theo nhiều trường (như số điện thoại, tên bệnh nhân)
  - Xem xét thêm logic xử lý trùng lịch (conflict) khi đặt lịch mới

## 4. Người thực hiện
- **Người thực hiện**: bisosad1501
- **Ngày thực hiện**: 04/04/2024 