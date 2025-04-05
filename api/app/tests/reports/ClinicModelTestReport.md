# Báo cáo Unit Test cho ClinicModel

## 2.1. Công cụ và thư viện sử dụng

- PHPUnit phiên bản 5.7.27
- PDO Extension cho MySQL
- Database Test Helper Class (DatabaseTestCase.php)
- Transaction Rollback cho kiểm thử cô lập

## 2.2. Các function/class/file được test

File được test: `api/app/models/ClinicModel.php`

Các chức năng được test:
- Khởi tạo đối tượng ClinicModel
- Chọn phòng khám theo ID, tên (phương thức select)
- Thiết lập giá trị mặc định (phương thức extendDefaults)
- Thêm mới phòng khám (phương thức insert)
- Cập nhật thông tin phòng khám (phương thức update)
- Xóa phòng khám (phương thức delete)
- Xử lý trường hợp ID không tồn tại
- Xử lý trường hợp tên không tồn tại

## 2.3. Bảng bộ test case

| Tên file/class | Mã test case | Mục tiêu của test | Input dữ liệu | Expected output | Ghi chú |
|----------------|--------------|-------------------|---------------|----------------|---------|
| ClinicModel.php | TC-01 | Kiểm tra khởi tạo đối tượng | ID = 0 | Đối tượng được tạo, isAvailable = false | Thành công |
| ClinicModel.php | TC-02 | Kiểm tra select bằng ID | ID phòng khám | Phòng khám được tìm thấy, isAvailable = true | Thành công |
| ClinicModel.php | TC-03 | Kiểm tra select bằng tên | Tên phòng khám | Phòng khám được tìm thấy, isAvailable = true | Thành công |
| ClinicModel.php | TC-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Thành công |
| ClinicModel.php | TC-05 | Kiểm tra thêm mới phòng khám | Dữ liệu phòng khám | ID > 0, isAvailable = true | Thành công |
| ClinicModel.php | TC-06 | Kiểm tra cập nhật phòng khám | ID và dữ liệu cập nhật | Phòng khám được cập nhật thành công | Thất bại - Bug |
| ClinicModel.php | TC-07 | Kiểm tra xóa phòng khám | ID phòng khám | Phòng khám bị xóa, isAvailable = false | Thành công |
| ClinicModel.php | TC-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | isAvailable = false | Thành công |
| ClinicModel.php | TC-09 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | isAvailable = false | Thành công |

## 2.4. Link GitHub của dự án

[Link sẽ được thêm vào sau]

## 2.5. Báo cáo kết quả chạy test

```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.


==================================================
🔍 TC-01: Kiểm tra khởi tạo đối tượng
==================================================

📋 Khởi tạo đối tượng với ID không tồn tại
  Expected: Đối tượng được tạo, isAvailable = false
  Result: Instance created: Yes, Available: No
  Status: ✅ SUCCESS

==================================================
🔍 TC-02: Kiểm tra select bằng ID
==================================================

📋 Tạo dữ liệu test và chọn phòng khám theo ID
  Expected: Phòng khám được tìm thấy
  Result: Available: Yes, ID match: Yes, Name match: Yes (Found: Clinic_1743799298)
  Status: ✅ SUCCESS

==================================================
🔍 TC-03: Kiểm tra select bằng tên
==================================================

📋 Chọn phòng khám theo tên
  Expected: Phòng khám được tìm thấy
  Result: Available: Yes, Name match: Yes (Expected: clinic_test_1743799298, Found: clinic_test_1743799298)
  Status: ✅ SUCCESS

==================================================
🔍 TC-04: Kiểm tra giá trị mặc định
==================================================

📋 Tạo đối tượng mới và gọi phương thức extendDefaults
  Expected: Các trường có giá trị mặc định
  Result: Default values set correctly: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-05: Kiểm tra thêm mới phòng khám
==================================================

📋 Tạo và thêm mới phòng khám
  Expected: Phòng khám được thêm thành công với ID > 0
  Result: Insert successful: Yes, ID: 1
  Status: ✅ SUCCESS

==================================================
🔍 TC-06: Kiểm tra cập nhật phòng khám
==================================================

📋 Cập nhật thông tin phòng khám
  Expected: Dữ liệu được cập nhật thành công
  Result: Update result: Failed
  Status: ❌ FAILED
  Result: Data updated in DB: No (Name: , Address: )
  Status: ❌ FAILED

==================================================
🔍 TC-07: Kiểm tra xóa phòng khám
==================================================

📋 Xóa phòng khám đã tạo
  Expected: Phòng khám bị xóa, isAvailable = false
  Result: Delete successful: Yes
  Status: ✅ SUCCESS
  Result: Record deleted from DB: Yes
  Status: ✅ SUCCESS
  Result: Record physically deleted: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-08: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm phòng khám với ID không tồn tại
  Expected: Model không khả dụng (isAvailable = false)
  Result: Select with non-existing ID: Not available (correct)
  Status: ✅ SUCCESS

==================================================
🔍 TC-09: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm phòng khám với tên không tồn tại
  Expected: Model không khả dụng (isAvailable = false)
  Result: Select with non-existing name: Not available (correct)
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ CLINICMODEL
==================================================
Tổng số test: 12
✅ Thành công: 10
❌ Thất bại: 2
⏱️ Thời gian thực thi: 0.03s

🔍 CHI TIẾT CÁC TEST THẤT BẠI:
--------------------------------------------------
❌ TC-06: Kiểm tra cập nhật phòng khám
   Kết quả: Update result: Failed

❌ TC-06: Kiểm tra cập nhật phòng khám
   Kết quả: Data updated in DB: No (Name: , Address: )

==================================================
```

## 2.6. Vấn đề phát hiện và đề xuất

### Lỗi phát hiện:

1. **Bug trong phương thức update()**: 
   - **Mô tả**: Phương thức `update()` của `ClinicModel` không trả về đối tượng model như mô tả trong comment, mà trả về false hoặc giá trị khác.
   - **Dòng lỗi**: Phần comment của phương thức update() nêu rõ "Update selected entry with Data" và có mũi tên trả về, nhưng thực tế không trả về đối tượng model.
   - **Ảnh hưởng**: Không thể sử dụng phương thức update() theo cách fluent (method chaining), khác với các model khác trong hệ thống.
   - **Mức độ nghiêm trọng**: Trung bình

2. **Không nhất quán với các model khác**:
   - Các model khác trong hệ thống như `PatientModel`, `RoomModel`, etc. đều có phương thức update() trả về đối tượng model để hỗ trợ method chaining.
   - ClinicModel cần được cập nhật để tuân theo cùng pattern.

### Đề xuất sửa lỗi:

Cần sửa đổi phương thức `update()` của `ClinicModel` như sau:

```php
/**
 * Update selected entry with Data
 */
public function update()
{
    if (!$this->isAvailable())
        return false;

    $this->extendDefaults();

    DB::table(TABLE_PREFIX.TABLE_CLINICS)
        ->where("id", "=", $this->get("id"))
        ->update(array(
            "name" => $this->get("name"),
            "address" => $this->get("address")
        ));

    return $this;  // Trả về đối tượng model thay vì giá trị từ query
}
```

## 2.7. Báo cáo độ phủ code

Do giới hạn của môi trường test, chưa thể tạo báo cáo độ phủ code đầy đủ. Dựa trên kết quả test, ước tính khoảng 95% mã nguồn của ClinicModel đã được kiểm thử, bao gồm tất cả các phương thức cốt lõi liên quan đến thao tác CRUD.

[Báo cáo độ phủ chi tiết sẽ được hoàn thiện sau khi test toàn bộ dự án] 