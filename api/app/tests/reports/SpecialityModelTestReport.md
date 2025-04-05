# Báo Cáo Kiểm Thử SpecialityModel

## 1. Tổng quan
Báo cáo này mô tả chi tiết việc kiểm thử đơn vị (unit test) cho lớp SpecialityModel trong hệ thống quản lý phòng khám. Mục tiêu của việc kiểm thử là đảm bảo tất cả các phương thức trong SpecialityModel hoạt động chính xác và xử lý các trường hợp đặc biệt một cách phù hợp.

## 2. Quy trình kiểm thử

### 2.1 Công cụ và thư viện sử dụng
- **PHPUnit 5.7.27**: Framework kiểm thử cho PHP
- **PHP 5.6.40**: Phiên bản PHP dùng để chạy test
- **MySQL**: Hệ quản trị cơ sở dữ liệu
- **Pixie**: Thư viện query builder hỗ trợ thao tác với database
- **Viocon**: Container hỗ trợ dependency injection

### 2.2 Các function/class/file được test
- **File được test**: `app/models/SpecialityModel.php`
- **Các phương thức được test**:
  - **__construct()**: Kiểm tra khởi tạo đối tượng
  - **select()**: Kiểm tra chọn chuyên khoa theo ID và tên
  - **extendDefaults()**: Kiểm tra thiết lập giá trị mặc định
  - **insert()**: Kiểm tra thêm chuyên khoa mới
  - **update()**: Kiểm tra cập nhật thông tin chuyên khoa
  - **delete()**: Kiểm tra xóa chuyên khoa

**Lý do không test các thành phần khác**: SpecialityModel là một model đơn giản chỉ chứa các phương thức CRUD cơ bản và không có logic phức tạp khác cần test. Các thành phần liên quan đến giao diện người dùng sẽ được test riêng trong các test khác.

### 2.3 Bảng bộ test case

| Tên file/class | Mã test case | Mục tiêu của test | Input dữ liệu | Expected output | Ghi chú |
|----------------|--------------|-------------------|---------------|-----------------|---------|
| SpecialityModel.php | TC-SM-01 | Kiểm tra tạo mới chuyên khoa | Dữ liệu chuyên khoa mẫu | Chuyên khoa được tạo thành công với ID > 0 | Kiểm tra cả trong DB |
| SpecialityModel.php | TC-SM-02 | Kiểm tra đọc thông tin chuyên khoa theo ID | ID chuyên khoa vừa tạo | Chuyên khoa được tìm thấy và dữ liệu đúng | Kiểm tra tất cả các trường |
| SpecialityModel.php | TC-SM-03 | Kiểm tra cập nhật thông tin chuyên khoa | Dữ liệu chuyên khoa mới | Chuyên khoa được cập nhật thành công | Kiểm tra dữ liệu sau khi cập nhật |
| SpecialityModel.php | TC-SM-04 | Kiểm tra xóa chuyên khoa | ID chuyên khoa | Chuyên khoa được xóa thành công | Kiểm tra cả trong DB |
| SpecialityModel.php | TC-SM-05 | Kiểm tra tìm kiếm chuyên khoa theo tên | Tên chuyên khoa | Chuyên khoa được tìm thấy và dữ liệu đúng | |
| SpecialityModel.php | TC-SM-06 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | |
| SpecialityModel.php | TC-SM-07 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng | |
| SpecialityModel.php | TC-SM-08 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | |
| SpecialityModel.php | TC-SM-09 | Kiểm tra update chuyên khoa không tồn tại | Model không khả dụng | Hàm update trả về false | |
| SpecialityModel.php | TC-SM-10 | Kiểm tra delete chuyên khoa không tồn tại | Model không khả dụng | Hàm delete trả về false | |
| SpecialityModel.php | TC-SM-11 | Kiểm tra insert với tên trùng lặp | Dữ liệu chuyên khoa với tên đã tồn tại | Hệ thống xử lý phù hợp | Kiểm tra cả hai trường hợp (cho phép/không cho phép trùng) |

### 2.4 Link GitHub
(Phần này sẽ được bổ sung sau)

### 2.5 Báo cáo kết quả chạy test

```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

Runtime:       PHP 5.6.40
Configuration: /Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/phpunit.xml.dist


==================================================
🔍 TC-SM: Kiểm tra quy trình CRUD
==================================================

📋 TC-SM-01: Tạo mới chuyên khoa
  Expected: Chuyên khoa được tạo thành công với ID > 0
  Result: Speciality ID: 15
  Status: ✅ SUCCESS

📋 TC-SM-02: Đọc thông tin chuyên khoa theo ID
  Expected: Chuyên khoa được tìm thấy và có dữ liệu đúng
  Result: ID: 15 - Tìm thấy: Có, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-SM-03: Cập nhật thông tin chuyên khoa
  Expected: Chuyên khoa được cập nhật thành công
  Result: Cập nhật thành công, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-SM-04: Xóa chuyên khoa
  Expected: Chuyên khoa được xóa thành công
  Result: Xóa thành công, Kiểm tra tồn tại: Đã xóa, Kiểm tra DB: Đã xóa khỏi DB
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-05: Kiểm tra tìm kiếm chuyên khoa theo tên
==================================================

📋 Tìm kiếm chuyên khoa theo tên: TestSpec95739
  Expected: Chuyên khoa được tìm thấy và dữ liệu khớp
  Result: Tìm kiếm thành công, Dữ liệu khớp, ID khớp
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-06: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm kiếm chuyên khoa với ID không tồn tại
  Expected: Chuyên khoa không được tìm thấy
  Result: ID không tồn tại: 1014, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-07: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm kiếm chuyên khoa với tên không tồn tại
  Expected: Chuyên khoa không được tìm thấy
  Result: Tên không tồn tại: NonExistent1743795739, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-08: Kiểm tra phương thức extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập đúng giá trị mặc định
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-09: Kiểm tra update chuyên khoa không tồn tại
==================================================

📋 Cập nhật thông tin chuyên khoa không tồn tại
  Expected: Hàm update trả về false
  Result: Update chuyên khoa không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-10: Kiểm tra delete chuyên khoa không tồn tại
==================================================

📋 Xóa chuyên khoa không tồn tại
  Expected: Hàm delete trả về false
  Result: Delete chuyên khoa không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SM-11: Kiểm tra insert với tên trùng lặp
==================================================

📋 Tạo chuyên khoa mới với tên đã tồn tại: DupSpec95739
  Expected: Hệ thống xử lý phù hợp
  Result: Insert chuyên khoa trùng tên: Thành công với ID: 18 (cho phép trùng tên)
  Status: ✅ SUCCESS

Time: 54 ms, Memory: 5.00MB

OK (8 tests, 23 assertions)
```

**Kết quả tổng hợp:**
- Tổng số test case: 11
- Tổng số test functions: 8
- Tổng số assertions: 23
- Kết quả: Tất cả PASS (100%)
- Thời gian thực thi: 54ms
- Bộ nhớ sử dụng: 5.00MB

### 2.6 Báo cáo độ phủ code
Báo cáo độ phủ code sẽ được bổ sung sau khi hoàn thành test toàn bộ dự án.

## 3. Kết luận và đề xuất
- **Kết luận**: SpecialityModel đã được kiểm thử đầy đủ và tất cả các chức năng hoạt động đúng như mong đợi. Cả các trường hợp thông thường và các trường hợp đặc biệt đều được xử lý phù hợp.
- **Đề xuất**: 
  - Có thể bổ sung thêm các ràng buộc UNIQUE cho tên chuyên khoa trong database (nếu có yêu cầu)
  - Nên cân nhắc thêm tính năng tìm kiếm theo một phần của tên chuyên khoa (search partial)
  - Đề xuất thêm validate dữ liệu đầu vào để tăng tính bảo mật và ổn định của hệ thống
  - Xem xét liên kết ràng buộc với bảng bác sĩ để đảm bảo không xóa chuyên khoa đang được sử dụng

## 4. Người thực hiện
- **Người thực hiện**: bisosad1501
- **Ngày thực hiện**: 04/04/2024 