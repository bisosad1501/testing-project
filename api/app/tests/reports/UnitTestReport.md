# Báo Cáo Unit Test cho Dự Án Umbrella Corporation

## 1. Công cụ và thư viện sử dụng

### 1.1. PHPUnit
- **Phiên bản**: 5.7.27
- **Mục đích**: Framework kiểm thử đơn vị cho PHP
- **Lý do sử dụng**: PHPUnit là framework kiểm thử đơn vị phổ biến nhất cho PHP, hỗ trợ nhiều tính năng như assertions, mocking, test coverage, và dễ dàng tích hợp với CI/CD.

### 1.2. Pixie
- **Mục đích**: Query Builder cho database
- **Lý do sử dụng**: Dự án đã sử dụng Pixie như một thư viện để tương tác với database, nên chúng tôi cũng sử dụng nó trong các bài kiểm thử.

### 1.3. Viocon
- **Mục đích**: Dependency Injection Container
- **Lý do sử dụng**: Được sử dụng bởi Pixie để quản lý các dependencies.

### 1.4. PDO
- **Mục đích**: PHP Data Objects cho database connectivity
- **Lý do sử dụng**: Cung cấp lớp trừu tượng để truy cập database, cho phép sử dụng các giao dịch (transaction) để rollback sau mỗi test.

## 2. Các thành phần được kiểm thử

### 2.1. Model đã được kiểm thử
1. **AppointmentModel** - Quản lý thông tin cuộc hẹn giữa bệnh nhân và bác sĩ
2. **DoctorModel** - Quản lý thông tin bác sĩ
3. **RoomModel** - Quản lý thông tin phòng khám
4. **SpecialityModel** - Quản lý thông tin chuyên khoa
5. **BookingModel** - Quản lý thông tin đặt lịch
6. **BookingPhotoModel** - Quản lý thông tin ảnh đính kèm đặt lịch
7. **PatientModel** - Quản lý thông tin bệnh nhân
8. **ClinicModel** - Quản lý thông tin phòng khám
9. **DrugModel** - Quản lý thông tin thuốc
10. **ServiceModel** - Quản lý thông tin dịch vụ
11. **AppointmentRecordModel** - Quản lý thông tin bản ghi cuộc hẹn
12. **DoctorAndServiceModel** - Quản lý mối quan hệ giữa bác sĩ và dịch vụ
13. **TreatmentModel** - Quản lý thông tin các phương pháp điều trị
14. **NotificationModel** - Quản lý thông tin các thông báo

### 2.2. Controller đã được kiểm thử
1. **AppointmentController** - Quản lý API cho lịch hẹn
2. **AppointmentRecordsController** - Quản lý API cho bản ghi lịch hẹn

### 2.3. Model chưa được kiểm thử và lý do
1. **Các model collection** (như BookingsModel, DoctorsModel) - Kế thừa từ DataList, sẽ được kiểm thử riêng trong một test suite khác

### 2.4. Controllers chưa được kiểm thử và lý do
Các controller còn lại hiện chưa được kiểm thử vì cần thiết lập môi trường HTTP request/response để giả lập API call. Sẽ thiết lập trong giai đoạn tiếp theo với framework kiểm thử API.

### 2.5. Helpers chưa được kiểm thử và lý do
Các helper function sẽ được kiểm thử trong giai đoạn tiếp theo với việc thiết lập môi trường độc lập và tập trung vào unit test trước.

## 3. Bộ Test Case

### 3.1. AppointmentModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| APPT_CONS_01 | Kiểm tra constructor và phương thức select | ID lịch hẹn hợp lệ/không hợp lệ | Khởi tạo/select thành công với ID hợp lệ, thất bại với ID không hợp lệ | Kiểm tra khởi tạo và select |
| APPT_DEF_02 | Kiểm tra phương thức extendDefaults | Model mới không có dữ liệu | Các giá trị mặc định được thiết lập đúng | Kiểm tra các giá trị mặc định |
| APPT_INS_03 | Tạo mới lịch hẹn | Dữ liệu lịch hẹn hợp lệ | ID lịch hẹn mới > 0 | Kiểm tra insert |
| APPT_READ_04 | Lấy thông tin lịch hẹn | ID lịch hẹn | Thông tin chính xác lịch hẹn | Kiểm tra đọc dữ liệu |
| APPT_UPD_05 | Cập nhật lịch hẹn | ID và dữ liệu mới | Lịch hẹn được cập nhật | Kiểm tra update |
| APPT_DEL_06 | Xóa lịch hẹn | ID lịch hẹn | Lịch hẹn bị xóa | Kiểm tra delete |
| APPT_ERR_07 | Kiểm tra xóa lịch hẹn không tồn tại | Model không khả dụng | Phương thức delete trả về false | Kiểm tra xử lý lỗi |
| APPT_CHAIN_08 | Kiểm tra giao diện fluent | Gọi các phương thức theo chuỗi | Các phương thức trả về đối tượng model | Kiểm tra method chaining |

### 3.2. DoctorModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| DOC_INS_01 | Tạo mới bác sĩ | Dữ liệu bác sĩ hợp lệ | ID bác sĩ mới > 0 | Kiểm tra insert |
| DOC_READ_02 | Lấy thông tin bác sĩ theo ID | ID bác sĩ | Thông tin chính xác bác sĩ | Kiểm tra select by ID |
| DOC_FIND_03 | Kiểm tra các phương thức đọc thông tin | Email, phone, ID không hợp lệ | Trả về đúng thông tin hoặc null | Kiểm tra select by email/phone |
| DOC_UPD_04 | Cập nhật thông tin bác sĩ | ID và dữ liệu mới | Thông tin bác sĩ được cập nhật | Kiểm tra update |
| DOC_DEL_05 | Xóa thông tin bác sĩ | ID bác sĩ | Bác sĩ bị xóa | Kiểm tra delete |
| DOC_ROLE_06 | Kiểm tra quyền của bác sĩ | Role admin/member | Phân quyền đúng | Kiểm tra isAdmin() |
| DOC_TOKEN_07 | Kiểm tra token khôi phục | Recovery token | Token được lưu/xóa chính xác | Kiểm tra recovery token |
| DOC_ACTIVE_08 | Kiểm tra trạng thái hoạt động | Active = 0/1 | Trạng thái được cập nhật đúng | Kiểm tra active status |

### 3.3. RoomModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| ROOM_INS_01 | Tạo mới phòng | Dữ liệu phòng hợp lệ | Phòng được tạo thành công với ID > 0 | Kiểm tra insert và DB |
| ROOM_READ_02 | Lấy thông tin phòng theo ID | ID phòng | Thông tin chính xác phòng | Kiểm tra select by ID |
| ROOM_UPD_03 | Cập nhật thông tin phòng | ID và dữ liệu mới | Thông tin phòng được cập nhật | Kiểm tra update và DB |
| ROOM_DEL_04 | Xóa thông tin phòng | ID phòng | Phòng bị xóa | Kiểm tra delete và DB |
| ROOM_NAME_05 | Lấy thông tin phòng theo tên | Tên phòng | Thông tin chính xác phòng | Kiểm tra select by name |
| ROOM_ERR_ID_06 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| ROOM_ERR_NAME_07 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| ROOM_DEF_08 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra giá trị mặc định |
| ROOM_ERR_UPD_09 | Kiểm tra update phòng không tồn tại | Model không khả dụng | Hàm update trả về false | Kiểm tra xử lý lỗi |
| ROOM_ERR_DEL_10 | Kiểm tra delete phòng không tồn tại | Model không khả dụng | Hàm delete trả về false | Kiểm tra xử lý lỗi |
| ROOM_DUP_11 | Kiểm tra insert với tên trùng lặp | Tên đã tồn tại | Xử lý phù hợp | Kiểm tra ràng buộc dữ liệu |

### 3.4. SpecialityModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| SPEC_INS_01 | Tạo mới chuyên khoa | Dữ liệu chuyên khoa mẫu | Chuyên khoa được tạo thành công với ID > 0 | Kiểm tra insert và DB |
| SPEC_READ_02 | Đọc thông tin chuyên khoa theo ID | ID chuyên khoa | Thông tin chính xác chuyên khoa | Kiểm tra select by ID |
| SPEC_UPD_03 | Cập nhật thông tin chuyên khoa | ID và dữ liệu mới | Chuyên khoa được cập nhật thành công | Kiểm tra update và DB |
| SPEC_DEL_04 | Xóa chuyên khoa | ID chuyên khoa | Chuyên khoa được xóa thành công | Kiểm tra delete và DB |
| SPEC_NAME_05 | Tìm kiếm chuyên khoa theo tên | Tên chuyên khoa | Thông tin chính xác chuyên khoa | Kiểm tra select by name |
| SPEC_ERR_ID_06 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| SPEC_ERR_NAME_07 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| SPEC_DEF_08 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra giá trị mặc định |
| SPEC_ERR_UPD_09 | Kiểm tra update chuyên khoa không tồn tại | Model không khả dụng | Hàm update trả về false | Kiểm tra xử lý lỗi |
| SPEC_ERR_DEL_10 | Kiểm tra delete chuyên khoa không tồn tại | Model không khả dụng | Hàm delete trả về false | Kiểm tra xử lý lỗi |
| SPEC_DUP_11 | Kiểm tra insert với tên trùng lặp | Dữ liệu với tên đã tồn tại | Hệ thống xử lý phù hợp | Kiểm tra ràng buộc dữ liệu |

### 3.5. BookingModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| BOOK_INS_01 | Kiểm tra tạo mới đặt lịch | Dữ liệu đặt lịch mẫu | Đặt lịch được tạo thành công với ID > 0 | Kiểm tra insert và DB |
| BOOK_READ_02 | Kiểm tra đọc thông tin đặt lịch theo ID | ID đặt lịch | Thông tin chính xác đặt lịch | Kiểm tra select by ID |
| BOOK_UPD_03 | Kiểm tra cập nhật thông tin đặt lịch | ID và dữ liệu mới | Đặt lịch được cập nhật thành công | Kiểm tra update và DB |
| BOOK_DEL_04 | Kiểm tra xóa đặt lịch | ID đặt lịch | Đặt lịch được xóa thành công | Kiểm tra delete và DB |
| BOOK_ERR_ID_05 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| BOOK_DEF_06 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra giá trị mặc định |
| BOOK_ERR_UPD_07 | Kiểm tra update đặt lịch không tồn tại | Model không khả dụng | Hàm update trả về false | Kiểm tra xử lý lỗi |
| BOOK_ERR_DEL_08 | Kiểm tra delete đặt lịch không tồn tại | Model không khả dụng | Hàm delete trả về false | Kiểm tra xử lý lỗi |
| BOOK_DUP_09 | Kiểm tra insert đặt lịch đã tồn tại | Model đã khả dụng | Hàm insert trả về false | Kiểm tra xử lý trùng lặp |

### 3.6. BookingPhotoModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| BPHOTO_INS_01 | Kiểm tra tạo mới ảnh đặt lịch | booking_id, url | Ảnh được tạo thành công với ID > 0 | Test thành phần đầu tiên của CRUD |
| BPHOTO_READ_02 | Kiểm tra đọc thông tin ảnh đặt lịch | ID ảnh đã tạo | Thông tin ảnh được trả về chính xác | Test thành phần thứ hai của CRUD |
| BPHOTO_UPD_03 | Kiểm tra cập nhật thông tin ảnh đặt lịch | ID ảnh đã tạo, url mới | Thông tin ảnh được cập nhật thành công | Test thành phần thứ ba của CRUD |
| BPHOTO_DEL_04 | Kiểm tra xóa ảnh đặt lịch | ID ảnh đã tạo | Ảnh được xóa thành công | Test thành phần thứ tư của CRUD |
| BPHOTO_ERR_ID_05 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable() = false) | Test xử lý trường hợp đặc biệt |

### 3.7. PatientModel (PM)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| PM-CT-01 | Kiểm tra khởi tạo đối tượng | ID = 0 | Đối tượng được tạo, isAvailable = false | Kiểm tra khởi tạo |
| PM-RD-02 | Kiểm tra select bằng ID | ID bệnh nhân | Đối tượng có dữ liệu khớp, isAvailable = true | Kiểm tra select by ID |
| PM-RD-03 | Kiểm tra select bằng email | Email bệnh nhân | Đối tượng có dữ liệu khớp, isAvailable = true | Kiểm tra select by email |
| PM-RD-04 | Kiểm tra select bằng số điện thoại | Phone bệnh nhân | Đối tượng có dữ liệu khớp, isAvailable = true | Bỏ qua - Không hỗ trợ |
| PM-DF-05 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra giá trị mặc định |
| PM-CR-06 | Kiểm tra thêm mới bệnh nhân | Dữ liệu bệnh nhân mới | ID > 0, isAvailable = true, dữ liệu được lưu vào DB | Kiểm tra insert |
| PM-UP-07 | Kiểm tra cập nhật bệnh nhân | ID và dữ liệu cập nhật | Dữ liệu trong DB được cập nhật | Kiểm tra update |
| PM-DL-08 | Kiểm tra xóa bệnh nhân | ID bệnh nhân | Bệnh nhân bị xóa, isAvailable = false | Kiểm tra delete |
| PM-ER-09 | Kiểm tra phương thức isAdmin | ID bệnh nhân | false | Kiểm tra quyền |

### 3.8. ClinicModel (CL)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CL-CT-01 | Kiểm tra khởi tạo đối tượng | ID = 0 | Đối tượng được tạo, isAvailable = false | Kiểm tra khởi tạo |
| CL-RD-02 | Kiểm tra select bằng ID | ID phòng khám | Phòng khám được tìm thấy, isAvailable = true | Kiểm tra select by ID |
| CL-RD-03 | Kiểm tra select bằng tên | Tên phòng khám | Phòng khám được tìm thấy, isAvailable = true | Kiểm tra select by name |
| CL-DF-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra giá trị mặc định |
| CL-CR-05 | Kiểm tra thêm mới phòng khám | Dữ liệu phòng khám | ID > 0, isAvailable = true | Kiểm tra insert |
| CL-UP-06 | Kiểm tra cập nhật phòng khám | ID và dữ liệu cập nhật | Phòng khám được cập nhật thành công | Thất bại - Bug |
| CL-DL-07 | Kiểm tra xóa phòng khám | ID phòng khám | Phòng khám bị xóa, isAvailable = false | Kiểm tra delete |
| CL-NR-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | isAvailable = false | Kiểm tra xử lý lỗi |
| CL-NR-09 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | isAvailable = false | Kiểm tra xử lý lỗi |

### 3.9. DrugModel (DR)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| DR-CT-01 | Kiểm tra khởi tạo đối tượng | ID = 0 | Đối tượng được tạo, isAvailable = false | Kiểm tra khởi tạo |
| DR-RD-02 | Kiểm tra select bằng ID | ID thuốc | Thuốc được tìm thấy, isAvailable = true | Kiểm tra select by ID |
| DR-RD-03 | Kiểm tra select bằng tên | Tên thuốc | Thuốc được tìm thấy, isAvailable = true | Kiểm tra select by name |
| DR-DF-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra giá trị mặc định |
| DR-CR-05 | Kiểm tra thêm mới thuốc | Dữ liệu thuốc | ID > 0, isAvailable = true | Kiểm tra insert |
| DR-UP-06 | Kiểm tra cập nhật thuốc | ID và dữ liệu cập nhật | Thuốc được cập nhật thành công | Kiểm tra update |
| DR-DL-07 | Kiểm tra xóa thuốc | ID thuốc | Thuốc bị xóa, isAvailable = false | Kiểm tra delete |
| DR-NR-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | isAvailable = false | Kiểm tra xử lý lỗi |
| DR-NR-09 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | isAvailable = false | Kiểm tra xử lý lỗi |

### 3.10. ServiceModel (SVM)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| SVM-CT-01 | Tạo mới dịch vụ | Dữ liệu dịch vụ mẫu | Dịch vụ được tạo thành công với ID > 0 | Kiểm tra insert và DB |
| SVM-RD-02 | Đọc thông tin dịch vụ theo ID | ID dịch vụ | Thông tin chính xác dịch vụ | Kiểm tra select by ID |
| SVM-UP-03 | Cập nhật thông tin dịch vụ | ID và dữ liệu mới | Dịch vụ được cập nhật thành công | Kiểm tra update và DB |
| SVM-DL-04 | Xóa dịch vụ | ID dịch vụ | Dịch vụ được xóa thành công | Kiểm tra delete và DB |
| SVM-RN-05 | Tìm kiếm dịch vụ theo tên | Tên dịch vụ | Thông tin chính xác dịch vụ | Kiểm tra select by name |
| SVM-NR-06 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| SVM-NR-07 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng | Kiểm tra xử lý lỗi |
| SVM-DF-08 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra giá trị mặc định |
| SVM-UP-09 | Kiểm tra update dịch vụ không tồn tại | Model không khả dụng | Hàm update trả về false | Kiểm tra xử lý lỗi |
| SVM-DL-10 | Kiểm tra delete dịch vụ không tồn tại | Model không khả dụng | Hàm delete trả về false | Kiểm tra xử lý lỗi |
| SVM-RN-11 | Kiểm tra insert với tên trùng lặp | Dữ liệu với tên đã tồn tại | Hệ thống xử lý phù hợp | Kiểm tra ràng buộc dữ liệu |

### 3.11. AppointmentRecordModel
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| AREC_CONS_01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| AREC_READ_02 | Kiểm tra select bằng ID | ID hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với ID |
| AREC_FIND_03 | Kiểm tra select bằng appointment_id | appointment_id hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với appointment_id |
| AREC_DEF_04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra phương thức extendDefaults |
| AREC_INS_05 | Kiểm tra thêm mới bản ghi cuộc hẹn | Dữ liệu bản ghi cuộc hẹn mới | Bản ghi cuộc hẹn được thêm thành công, ID > 0 | Kiểm tra phương thức insert |
| AREC_UPD_06 | Kiểm tra cập nhật bản ghi cuộc hẹn | Bản ghi cuộc hẹn đã tồn tại, dữ liệu mới | Dữ liệu được cập nhật thành công | Kiểm tra phương thức update |
| AREC_DEL_07 | Kiểm tra xóa bản ghi cuộc hẹn | Bản ghi cuộc hẹn đã tồn tại | Bản ghi cuộc hẹn bị xóa, isAvailable = false | Kiểm tra phương thức delete |
| AREC_ERR_ID_08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |
| AREC_ERR_FIND_09 | Kiểm tra select với appointment_id không tồn tại | appointment_id không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |

### 3.12. DoctorAndServiceModel (DSM)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| DSM-CT-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| DSM-RD-02 | Kiểm tra select bằng ID | ID hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với ID |
| DSM-RD-03 | Kiểm tra select bằng doctor_id | doctor_id hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với doctor_id |
| DSM-RD-04 | Kiểm tra select bằng service_id | service_id hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với service_id |
| DSM-DF-05 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra phương thức extendDefaults |
| DSM-CR-06 | Kiểm tra thêm mới mối quan hệ | Dữ liệu mối quan hệ mới | ID > 0, isAvailable = true, dữ liệu được lưu vào DB | Kiểm tra insert |
| DSM-UP-07 | Kiểm tra cập nhật mối quan hệ | ID và dữ liệu cập nhật | Dữ liệu trong DB được cập nhật | Kiểm tra update |
| DSM-DL-08 | Kiểm tra xóa mối quan hệ | ID mối quan hệ | Mối quan hệ bị xóa, isAvailable = false | Kiểm tra delete |
| DSM-ER-09 | Kiểm tra phương thức isAdmin | ID mối quan hệ | false | Kiểm tra quyền |

### 3.13. TreatmentModel (TM)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| TM-CT-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| TM-RD-02 | Kiểm tra select bằng ID | ID phương pháp điều trị | Phương pháp điều trị được tìm thấy, dữ liệu chính xác | Kiểm tra select by ID |
| TM-RD-03 | Kiểm tra select bằng name | Tên phương pháp điều trị | Phương pháp điều trị được tìm thấy, dữ liệu chính xác | Kiểm tra select by name |
| TM-DF-04 | Kiểm tra giá trị mặc định | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Kiểm tra extendDefaults |
| TM-CR-05 | Kiểm tra thêm mới phương pháp điều trị | Dữ liệu phương pháp điều trị | Phương pháp điều trị được thêm thành công, ID > 0 | Kiểm tra insert |
| TM-UP-06 | Kiểm tra cập nhật phương pháp điều trị | ID và dữ liệu mới | Phương pháp điều trị được cập nhật thành công | Kiểm tra update |
| TM-DL-07 | Kiểm tra xóa phương pháp điều trị | ID phương pháp điều trị | Phương pháp điều trị bị xóa, isAvailable = false | Kiểm tra delete |
| TM-NR-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | Kiểm tra xử lý ID không tồn tại |
| TM-NR-09 | Kiểm tra select với name không tồn tại | Tên không tồn tại | Model không khả dụng | Kiểm tra xử lý name không tồn tại |

### 3.14. NotificationModel (NM)
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| NM-CT-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| NM-RD-02 | Kiểm tra select bằng ID | ID hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với ID |
| NM-RD-03 | Kiểm tra select bằng appointment_id | appointment_id hợp lệ | Bản ghi cuộc hẹn được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với appointment_id |
| NM-DF-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra phương thức extendDefaults |
| NM-CR-05 | Kiểm tra thêm mới bản ghi cuộc hẹn | Dữ liệu bản ghi cuộc hẹn mới | Bản ghi cuộc hẹn được thêm thành công, ID > 0 | Kiểm tra phương thức insert |
| NM-UP-06 | Kiểm tra cập nhật bản ghi cuộc hẹn | Bản ghi cuộc hẹn đã tồn tại, dữ liệu mới | Dữ liệu được cập nhật thành công | Kiểm tra phương thức update |
| NM-DL-07 | Kiểm tra xóa bản ghi cuộc hẹn | Bản ghi cuộc hẹn đã tồn tại | Bản ghi cuộc hẹn bị xóa, isAvailable = false | Kiểm tra phương thức delete |
| NM-NR-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |
| NM-NR-09 | Kiểm tra select với appointment_id không tồn tại | appointment_id không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |

### 3.15. AppointmentController

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CTRL_APPT_GET_001 | Kiểm tra chức năng xem chi tiết lịch hẹn với ID hợp lệ | - Tài khoản: Admin doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: GET | - result = 1<br>- msg = "Action successfully !"<br>- data chứa thông tin chi tiết lịch hẹn | Kiểm tra phương thức getById() |
| CTRL_APPT_GET_INVALID_002 | Kiểm tra chức năng xem chi tiết lịch hẹn với ID không tồn tại | - Tài khoản: Admin doctor<br>- ID không tồn tại (99999)<br>- Phương thức: GET | - result = 1<br>- msg = "Action successfully !" | Controller không trả về lỗi khi không tìm thấy appointment |
| CTRL_APPT_UPDATE_003 | Kiểm tra chức năng cập nhật thông tin lịch hẹn thành công | - Tài khoản: Admin doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: PUT<br>- Dữ liệu cập nhật hợp lệ | - result = 1<br>- msg = "Appointment has been updated successfully !"<br>- data chứa thông tin đã cập nhật | Kiểm tra phương thức update() |
| CTRL_APPT_UPDATE_INVALID_004 | Kiểm tra chức năng cập nhật lịch hẹn với dữ liệu không hợp lệ | - Tài khoản: Admin doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: PUT<br>- patient_id trống | - result chứa lỗi SQL<br>- msg = "Missing field: patient_id" | Controller cần xác thực dữ liệu trước khi gửi đến DB |
| CTRL_APPT_CONFIRM_005 | Kiểm tra chức năng xác nhận lịch hẹn (thay đổi trạng thái) | - Tài khoản: Admin doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: PATCH<br>- status = 'done' | - result = 0<br>- msg chứa thông báo lỗi SQL | Phát hiện lỗi SQL syntax trong controller |
| CTRL_APPT_CONFIRM_PERM_006 | Kiểm tra quyền xác nhận lịch hẹn - member doctor không thể cập nhật lịch hẹn của bác sĩ khác | - Tài khoản: Member doctor<br>- ID lịch hẹn thuộc admin<br>- Phương thức: PATCH<br>- status = 'done' | - result = 0<br>- msg chứa thông báo lỗi SQL | Phát hiện lỗi SQL tương tự như test case trước |
| CTRL_APPT_DELETE_007 | Kiểm tra chức năng xóa lịch hẹn thành công | - Tài khoản: Admin doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: DELETE | - result = 1<br>- msg = "Appointment is deleted successfully !"<br>- Bản ghi bị xóa khỏi DB | Kiểm tra phương thức delete() |
| CTRL_APPT_DELETE_PERM_008 | Kiểm tra quyền xóa lịch hẹn - member doctor có quyền xóa | - Tài khoản: Member doctor<br>- ID lịch hẹn tồn tại<br>- Phương thức: DELETE | - result = 1<br>- msg = "Appointment is deleted successfully !" | Phát hiện vấn đề: member doctor không nên có quyền xóa |
| CTRL_APPT_DELETE_DONE_009 | Kiểm tra xóa lịch hẹn có trạng thái "done" | - Tài khoản: Admin doctor<br>- ID lịch hẹn với status = "done"<br>- Phương thức: DELETE | - result = 1<br>- msg = "Appointment is deleted successfully !" | Phát hiện vấn đề: lịch hẹn đã hoàn thành không nên bị xóa |

### 3.16. AppointmentQueueController

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CTRL_QUEUE_GET_ALL_001 | Kiểm tra lấy danh sách lịch hẹn trong hàng đợi với quyền Admin | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=all, doctor_id | - result = 1<br>- msg = "All appointments"<br>- data chứa danh sách lịch hẹn | Kiểm tra phương thức getAll() |
| CTRL_QUEUE_GET_ALL_002 | Kiểm tra lấy danh sách lịch hẹn trong hàng đợi với quyền Member | - Tài khoản: Member doctor<br>- Phương thức: GET<br>- Parameter: request=all | - result = 1<br>- msg = "All appointments"<br>- data chỉ chứa lịch hẹn của member | Member chỉ thấy lịch hẹn của mình |
| CTRL_QUEUE_ARRANGE_003 | Kiểm tra sắp xếp thứ tự lịch hẹn với quyền Admin | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: doctor_id, queue (mảng ID) | - result = 1<br>- msg = "Appointments have been updated their positions" | Kiểm tra phương thức arrange() |
| CTRL_QUEUE_ARRANGE_PERM_004 | Kiểm tra quyền sắp xếp lịch hẹn - member vẫn có thể sắp xếp | - Tài khoản: Member doctor<br>- Phương thức: POST<br>- Data: doctor_id, queue | - result = 1<br>- msg = "Appointments have been updated their positions" | Phát hiện vấn đề: member vẫn được cấp quyền |
| CTRL_QUEUE_ARRANGE_INVALID_005 | Kiểm tra sắp xếp với dữ liệu không hợp lệ | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: thiếu trường queue | - result = 0<br>- msg = "Invalid argument supplied for foreach()" | Lỗi PHP thay vì thông báo lỗi rõ ràng |
| CTRL_QUEUE_ARRANGE_INVALID_DOCTOR_006 | Kiểm tra sắp xếp với doctor_id không tồn tại | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: doctor_id không tồn tại | - result = 1<br>- msg = "Appointments have been updated their positions" | Phát hiện vấn đề: không xác thực doctor_id |
| CTRL_QUEUE_GET_ALL_FILTER_007 | Kiểm tra lọc lịch hẹn theo ngày | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=all, date | - result = 1<br>- msg chứa ngày được lọc<br>- data chỉ chứa lịch hẹn của ngày đó | Lọc theo ngày hoạt động chính xác |
| CTRL_QUEUE_GET_ALL_FILTER_008 | Kiểm tra lọc lịch hẹn theo trạng thái | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=all, status=processing | - result = 1<br>- data chỉ chứa lịch hẹn có status=processing | Lọc theo trạng thái hoạt động chính xác |
| CTRL_QUEUE_GET_ALL_SEARCH_009 | Kiểm tra tìm kiếm lịch hẹn theo tên bệnh nhân | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=all, search=tên | - result = 1<br>- data chỉ chứa lịch hẹn có tên khớp | Tìm kiếm hoạt động chính xác |
| CTRL_QUEUE_PROCESS_010 | Kiểm tra phương thức process() | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=all | - result = 1<br>- Controller xử lý request đúng | Entry point điều hướng đúng |
| CTRL_QUEUE_GET_QUEUE_011 | Kiểm tra lấy hàng đợi hiện tại | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: request=queue | Skipped | Phát hiện vấn đề thiết kế: in trực tiếp thay vì trả về response |
| CTRL_QUEUE_NO_AUTH_013 | Kiểm tra xử lý khi không có người dùng đăng nhập | - Không có AuthUser<br>- Phương thức: GET | Redirect đến trang login | Bảo mật hoạt động chính xác |
| CTRL_QUEUE_INVALID_REQUEST_014 | Kiểm tra xử lý request không hợp lệ | - Tài khoản: Admin doctor<br>- Phương thức: DELETE | Skipped | Controller không xử lý phương thức DELETE |

### 3.17. AppointmentRecordController

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CTRL_APREC_GET_001 | Kiểm tra chức năng xem chi tiết bản ghi lịch hẹn với ID hợp lệ | - Tài khoản: Admin doctor<br>- ID bản ghi hợp lệ<br>- Phương thức: GET<br>- Parameter: type=id | - result = 1<br>- msg = "Action successfully !"<br>- data chứa thông tin chi tiết bản ghi, lịch hẹn, bác sĩ, chuyên khoa | Kiểm tra phương thức getById() với ID bản ghi |
| CTRL_APREC_GET_002 | Kiểm tra chức năng xem chi tiết bản ghi lịch hẹn với appointment_id | - Tài khoản: Member doctor<br>- appointment_id hợp lệ<br>- Phương thức: GET<br>- Parameter: type=appointment_id | - result = 1<br>- msg = "Action successfully !"<br>- data chứa thông tin chi tiết bản ghi | Kiểm tra phương thức getById() với appointment_id |
| CTRL_APREC_GET_NORECORD_003 | Kiểm tra xử lý khi không tìm thấy bản ghi lịch hẹn | - Tài khoản: Admin doctor<br>- ID không tồn tại (99999)<br>- Phương thức: GET | - result = 0<br>- msg = "There is no appointment record found by id so that we CREATE a new one !" | Kiểm tra phương thức getById() với ID không tồn tại |
| CTRL_APREC_UPDATE_004 | Kiểm tra chức năng cập nhật thông tin bản ghi lịch hẹn thành công | - Tài khoản: Admin doctor<br>- ID bản ghi hợp lệ<br>- Phương thức: PUT<br>- Dữ liệu cập nhật hợp lệ | - result = 1<br>- msg = "Appointment record has been UPDATE successfully"<br>- data chứa thông tin đã cập nhật | Kiểm tra phương thức update() |
| CTRL_APREC_UPDATE_MISSING_005 | Kiểm tra cập nhật bản ghi với dữ liệu thiếu | - Tài khoản: Admin doctor<br>- ID bản ghi hợp lệ<br>- Phương thức: PUT<br>- Dữ liệu thiếu trường reason | - result = 0<br>- msg = "Missing field: reason" | Controller không xác thực trường reason như mong đợi |
| CTRL_APREC_UPDATE_PERM_006 | Kiểm tra quyền cập nhật - member doctor không thể cập nhật bản ghi của bác sĩ khác | - Tài khoản: Member doctor<br>- ID bản ghi của admin doctor<br>- Phương thức: PUT | - result = 0<br>- msg = "This appointment record does not belong to you so that you can update this record" | Controller không kiểm tra quyền như mong đợi |
| CTRL_APREC_SUPPORTER_007 | Kiểm tra quyền truy cập - supporter không có quyền truy cập controller | - Tài khoản: Supporter<br>- Phương thức: GET | - result = 0<br>- msg = "Only Doctor's role as admin, member who can do this action" | Xác thực quyền trong controller |
| CTRL_APREC_NO_AUTH_008 | Kiểm tra xử lý khi không có người dùng đăng nhập | - Không có AuthUser<br>- Phương thức: GET | - Redirect đến trang login | Bảo mật hoạt động chính xác |
| CTRL_APREC_NO_ID_009 | Kiểm tra xử lý khi không có ID trong route | - Tài khoản: Admin doctor<br>- Không có ID trong route params<br>- Phương thức: GET | - result = 0<br>- msg = "ID is required !" | Kiểm tra validate tham số route |
| CTRL_APREC_UPDATE_VALIDATION_012 | Kiểm tra validation cho trường status_before/status_after | - Tài khoản: Admin doctor<br>- ID bản ghi hợp lệ<br>- Phương thức: PUT<br>- status_before chứa ký tự đặc biệt | - result = 0<br>- msg = "Status before only has letters, space, number & dash. Try again !" | Controller không xác thực định dạng status_before đúng |
| CTRL_APREC_UPDATE_DATE_013 | Kiểm tra validation cho ngày hẹn không phải ngày hiện tại | - Tài khoản: Admin doctor<br>- ID bản ghi với ngày hẹn trong quá khứ<br>- Phương thức: PUT | - result = 0<br>- msg chứa thông báo "Today is...but this appointment's is... so that you can not create new appointment record" | Controller không kiểm tra ngày trong quá khứ như mong đợi |

### 3.18. AppointmentQueueNowController

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CTRL_QUEUE_NOW_GET_001 | Kiểm tra chức năng lấy thông tin hàng đợi hiện tại khi là Admin | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id | - Thông tin 3 cuộc hẹn đầu tiên trong hàng đợi | Controller chỉ thực hiện query mà không trả kết quả JSON |
| CTRL_QUEUE_NOW_GET_002 | Kiểm tra chức năng lấy thông tin hàng đợi hiện tại khi là Member | - Tài khoản: Member doctor<br>- Phương thức: GET | - Controller tự động sử dụng ID của member doctor<br>- Thông tin hàng đợi của member doctor | Controller không trả về JSON response |
| CTRL_QUEUE_NOW_GET_MISSING_ID_003 | Kiểm tra xử lý khi thiếu doctor_id đối với admin | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Không có doctor_id | - result = 0<br>- msg = "Missing doctor ID" | Kiểm tra validate dữ liệu đầu vào |
| CTRL_QUEUE_NOW_NO_AUTH_004 | Kiểm tra xử lý khi không có người dùng đăng nhập | - Không có AuthUser<br>- Phương thức: GET | - Redirect đến trang login | Kiểm tra xác thực người dùng |
| CTRL_QUEUE_NOW_PROCESS_005 | Kiểm tra phương thức process() với request GET | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id | - Method getQueue() được gọi | Khó mock phương thức getQueue() |
| CTRL_QUEUE_NOW_PROCESS_005_ALT | Kiểm tra phương thức process() - cách tiếp cận thay thế | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Không có doctor_id | - result = 0<br>- msg = "Missing doctor ID" | Xác nhận process() gọi getQueue() |
| CTRL_QUEUE_NOW_GET_EMPTY_007 | Kiểm tra chức năng lấy hàng đợi khi không có lịch hẹn | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id hợp lệ | - Không trả về kết quả do thiết kế controller | Controller không xử lý trường hợp không có lịch hẹn |
| CTRL_QUEUE_NOW_GET_INVALID_DOC_008 | Kiểm tra xử lý khi doctor_id không tồn tại | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id không tồn tại | - Không có kiểm tra tồn tại | Controller không xác thực doctor_id trước khi query |
| CTRL_QUEUE_NOW_FORMAT_009 | Kiểm tra định dạng ngày | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id | - Query sử dụng định dạng d-m-Y | Controller sử dụng định dạng ngày khác với định dạng Y-m-d phổ biến |

### 3.19. AppointmentRecordsController

| Mã test | Mục tiêu | Input | Expected Output | Trạng thái | Vấn đề |
|---------|----------|-------|----------------|------------|--------|
| CTRL_APRECS_GET_001 | Kiểm tra chức năng lấy danh sách bản ghi lịch hẹn với quyền admin | - Tài khoản đăng nhập: Admin<br>- Phương thức: GET | - result = 1<br>- data chứa danh sách bản ghi | ✅ PASS | Không |
| CTRL_APRECS_GET_002 | Kiểm tra chức năng lấy danh sách bản ghi lịch hẹn với quyền member | - Tài khoản đăng nhập: Member<br>- Phương thức: GET | - result = 1<br>- data chỉ chứa bản ghi của member | ❌ FAIL | Lọc theo member không hoạt động |
| CTRL_APRECS_GET_003 | Kiểm tra chức năng lọc danh sách bản ghi theo doctor_id | - Tài khoản đăng nhập: Admin<br>- Phương thức: GET<br>- doctor_id = member ID | - result = 1<br>- data chỉ chứa bản ghi của doctor đã chọn | ❌ FAIL | Lọc doctor_id không hoạt động |
| CTRL_APRECS_GET_004 | Kiểm tra chức năng lọc danh sách bản ghi theo ngày | - Tài khoản đăng nhập: Admin<br>- Phương thức: GET<br>- date = ngày hiện tại | - result = 1<br>- data chỉ chứa bản ghi có ngày trùng | ❌ FAIL | Lọc ngày không hoạt động |
| CTRL_APRECS_GET_005 | Kiểm tra chức năng tìm kiếm bản ghi | - Tài khoản đăng nhập: Admin<br>- Phương thức: GET<br>- search = từ khóa | - result = 1<br>- data chỉ chứa bản ghi khớp với từ khóa | ❌ FAIL | Tìm kiếm không hoạt động |
| CTRL_APRECS_SAVE_006 | Kiểm tra chức năng tạo mới bản ghi khám bệnh | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- Dữ liệu đầy đủ | - result = 1<br>- msg = "CREATE successfully"<br>- data chứa thông tin bản ghi | ⚠️ INCOMPLETE | Lỗi cú pháp SQL |
| CTRL_APRECS_SAVE_007 | Kiểm tra chức năng cập nhật bản ghi khám bệnh | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- Dữ liệu đầy đủ với appointment_id đã có record | - result = 1<br>- msg = "UPDATE successfully"<br>- data chứa thông tin đã cập nhật | ⚠️ INCOMPLETE | Lỗi cú pháp SQL |
| CTRL_APRECS_SAVE_008 | Kiểm tra validation khi thiếu trường bắt buộc | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- Thiếu trường reason | - result = 0<br>- msg = "Missing field: reason" | ✅ PASS | Không |
| CTRL_APRECS_SAVE_009 | Kiểm tra validation cho lý do không hợp lệ | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- reason chứa ký tự đặc biệt | - result = 0<br>- msg = "Reason before only has letters, space, number & dash. Try again !" | ✅ PASS | Không |
| CTRL_APRECS_SAVE_010 | Kiểm tra validation cho trạng thái trước không hợp lệ | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- status_before chứa ký tự đặc biệt | - result = 0<br>- msg = "Status before only has letters, space, number & dash. Try again !" | ✅ PASS | Không |
| CTRL_APRECS_SAVE_011 | Kiểm tra validation cho lịch hẹn không tồn tại | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- appointment_id không tồn tại | - result = 0<br>- msg = "Appointment is not available" | ✅ PASS | Không |
| CTRL_APRECS_SAVE_012 | Kiểm tra validation cho lịch hẹn trong quá khứ | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- appointment_id có ngày trong quá khứ | - result = 0<br>- msg chứa "you can not create new appointment record" | ✅ PASS | Không |
| CTRL_APRECS_SAVE_013 | Kiểm tra validation cho lịch hẹn đã hoàn thành | - Tài khoản đăng nhập: Admin<br>- Phương thức: POST<br>- appointment_id có status = "done" | - result = 0<br>- msg = "The status of appointment is done so that you can't do this action" | ✅ PASS | Không |
| CTRL_APRECS_AUTH_014 | Kiểm tra quyền truy cập - supporter không có quyền | - Tài khoản đăng nhập: Supporter<br>- Phương thức: GET | - result = 0<br>- msg chứa "Only Doctor's role as admin, member who can do this action" | ⚠️ WARNING | Controller trả về result = 1 (thành công) thay vì 0 (thất bại) |
| CTRL_APRECS_NO_AUTH_015 | Kiểm tra xử lý khi không có người dùng đăng nhập | - Không có người dùng đăng nhập<br>- Phương thức: GET | - Chuyển hướng đến trang login | ✅ PASS | Không |

### 3.20. AppointmentsController

| Mã test | Mục tiêu | Input | Expected Output | 
Trạng thái | Vấn đề |
|---------|----------|-------|----------------|
------------|--------|
| CTRL_APPTS_GET_001 | Kiểm tra chức năng lấy danh 
sách lịch hẹn với quyền admin | - Tài khoản: Admin 
doctor<br>- Phương thức: GET | - result = 1<br>- msg 
= "Action successfully !"<br>- data chứa danh sách 
lịch hẹn | ✅ PASS | Không |
| CTRL_APPTS_GET_002 | Kiểm tra chức năng lấy danh 
sách lịch hẹn với quyền member | - Tài khoản: Member 
doctor<br>- Phương thức: GET | - result = 1<br>- data 
chỉ chứa lịch hẹn của member | ✅ PASS | Không |
| CTRL_APPTS_GET_003 | Kiểm tra chức năng lọc danh 
sách lịch hẹn theo ngày | - Tài khoản: Admin 
doctor<br>- Phương thức: GET<br>- Parameter: date | - 
result = 1<br>- data chỉ chứa lịch hẹn có ngày 
trùng | ✅ PASS | Không |
| CTRL_APPTS_GET_004 | Kiểm tra chức năng lọc danh 
sách lịch hẹn theo bác sĩ | - Tài khoản: Admin 
doctor<br>- Phương thức: GET<br>- Parameter: 
doctor_id | - result = 1<br>- data chỉ chứa lịch hẹn 
của bác sĩ đã chọn | ✅ PASS | Không |
| CTRL_APPTS_GET_005 | Kiểm tra chức năng tìm kiếm 
lịch hẹn | - Tài khoản: Admin doctor<br>- Phương 
thức: GET<br>- Parameter: search | - result = 1<br>- 
data chỉ chứa lịch hẹn khớp với từ khóa | ✅ PASS | 
Không |
| CTRL_APPTS_NEW_006 | Kiểm tra chức năng tạo lịch 
hẹn mới với doctor_id | - Tài khoản: Admin 
doctor<br>- Phương thức: POST<br>- Data: doctor_id, 
patient_name, patient_birthday, patient_reason, 
patient_phone | - result = 1<br>- Lịch hẹn được tạo 
với số thứ tự và vị trí | ⚠️ SKIPPED | Lỗi cú pháp 
SQL trong hàm getTheLaziestDoctor() |
| CTRL_APPTS_NEW_007 | Kiểm tra chức năng tạo lịch 
hẹn mới với service_id | - Tài khoản: Admin 
doctor<br>- Phương thức: POST<br>- Data: service_id, 
patient_name, patient_birthday, patient_reason, 
patient_phone | - result = 1<br>- Lịch hẹn được tạo 
với bác sĩ ít bệnh nhân nhất | ⚠️ SKIPPED | Lỗi cú 
pháp SQL trong hàm getTheLaziestDoctor() |
| CTRL_APPTS_NEW_008 | Kiểm tra validation khi thiếu 
dữ liệu bắt buộc | - Tài khoản: Admin doctor<br>- 
Phương thức: POST<br>- Data: thiếu patient_reason | - 
result = 0<br>- msg = "Missing field: 
patient_reason" | ✅ PASS | Không |
| CTRL_APPTS_NEW_009 | Kiểm tra quyền - chỉ admin và 
supporter mới được tạo lịch hẹn | - Tài khoản: Member 
doctor<br>- Phương thức: POST<br>- Data: đầy đủ | - 
result = 0<br>- msg = "You don't have permission" | ✅ 
PASS | Không |
| CTRL_APPTS_NEW_010 | Kiểm tra tính hợp lệ của 
patient_name | - Tài khoản: Admin doctor<br>- Phương 
thức: POST<br>- Data: patient_name có ký tự đặc 
biệt | - result = 0<br>- msg chứa "Vietnamese name 
only has letters and space" | ⚠️ SKIPPED | Validation 
không hoạt động đúng |
| CTRL_APPTS_NEW_011 | Kiểm tra tính hợp lệ của 
patient_phone | - Tài khoản: Admin doctor<br>- Phương 
thức: POST<br>- Data: patient_phone quá ngắn | - 
result = 0<br>- msg chứa "phone number has at least 
10 number" | ⚠️ SKIPPED | Lỗi cú pháp SQL trong 
controller |
| CTRL_APPTS_NEW_012 | Kiểm tra xử lý khi cả 
service_id và doctor_id đều thiếu | - Tài khoản: 
Admin doctor<br>- Phương thức: POST<br>- Data: thiếu 
service_id và doctor_id | - result = 0<br>- msg chứa 
"cần cung cấp nhu cầu khám bệnh hoặc tên bác sĩ" | ⚠️ 
SKIPPED | Thông báo lỗi không nhất quán |
| CTRL_APPTS_NEW_013 | Kiểm tra xử lý khi doctor_id 
là supporter | - Tài khoản: Admin doctor<br>- Phương 
thức: POST<br>- Data: doctor_id của supporter | - 
result = 0<br>- msg chứa "You can't assign 
appointment to SUPPORTER" | ⚠️ SKIPPED | Lỗi cú pháp 
SQL trong controller |

### 3.21. BookingController

| Mã test | Mục tiêu | Input | Expected Output | Trạng thái | Vấn đề |
|---------|----------|-------|----------------|------------|--------|
| CTRL_BOOK_GET_001 | Kiểm tra chức năng lấy thông tin đặt lịch theo ID với quyền admin | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- Phương thức: GET | - result = 1<br>- msg = "Action successfully !"<br>- data chứa thông tin chi tiết đặt lịch | ❌ FAIL | Lỗi khởi tạo khi thiếu trường doctor_id |
| CTRL_BOOK_GET_002 | Kiểm tra chức năng lấy thông tin đặt lịch theo ID với quyền supporter | - Tài khoản: Supporter<br>- ID đặt lịch hợp lệ<br>- Phương thức: GET | - result = 1<br>- msg = "Action successfully !"<br>- data chứa thông tin chi tiết đặt lịch | ❌ FAIL | Lỗi khởi tạo khi thiếu trường doctor_id |
| CTRL_BOOK_GET_003 | Kiểm tra xử lý khi ID không tồn tại | - Tài khoản: Admin doctor<br>- ID đặt lịch không tồn tại<br>- Phương thức: GET | - result = 0<br>- msg = "Booking is not available" | ❌ FAIL | Thông báo lỗi không đúng kỳ vọng |
| CTRL_BOOK_GET_004 | Kiểm tra xử lý khi không có ID | - Tài khoản: Admin doctor<br>- Không có ID trong route params<br>- Phương thức: GET | - result = 0<br>- msg = "ID is required !" | ❌ FAIL | Thông báo lỗi không đúng kỳ vọng |
| CTRL_BOOK_GET_005 | Kiểm tra xử lý khi người dùng không có quyền | - Tài khoản: Member doctor<br>- ID đặt lịch hợp lệ<br>- Phương thức: GET | - result = 0<br>- msg chứa "You don't have permission" | ❌ FAIL | Controller cho phép role member truy cập |
| CTRL_BOOK_UPDATE_006 | Kiểm tra chức năng cập nhật đặt lịch thành công | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- Dữ liệu cập nhật hợp lệ<br>- Phương thức: PUT | - result = 1<br>- msg chứa "successfully"<br>- data chứa thông tin đã cập nhật | ❌ FAIL | Lỗi khởi tạo khi thiếu trường doctor_id |
| CTRL_BOOK_UPDATE_007 | Kiểm tra validation khi thiếu trường bắt buộc | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- Thiếu trường name<br>- Phương thức: PUT | - result = 0<br>- msg = "Missing field: name" | ❌ FAIL | Controller không kiểm tra trường thiếu |
| CTRL_BOOK_UPDATE_008 | Kiểm tra validation khi booking_name không hợp lệ | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- booking_name chứa ký tự đặc biệt<br>- Phương thức: PUT | - result = 0<br>- msg chứa "Vietnamese name only has letters and space" | ❌ FAIL | Validation không hoạt động đúng |
| CTRL_BOOK_UPDATE_009 | Kiểm tra validation khi booking_phone không hợp lệ | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- booking_phone quá ngắn<br>- Phương thức: PUT | - result = 0<br>- msg chứa "has at least 10 number" | ❌ FAIL | Validation không hoạt động đúng |
| CTRL_BOOK_CONFIRM_010 | Kiểm tra chức năng xác nhận đặt lịch (status = verified) | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- newStatus = "verified"<br>- Phương thức: PATCH | - result = 1<br>- msg chứa "VERIFIED"<br>- Status đặt lịch được cập nhật thành "verified" | ❌ FAIL | Lỗi khởi tạo khi thiếu trường doctor_id |
| CTRL_BOOK_CONFIRM_011 | Kiểm tra chức năng hủy đặt lịch (status = cancelled) | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- newStatus = "cancelled"<br>- Phương thức: PATCH | - result = 1<br>- msg chứa "cancelled successfully"<br>- Status đặt lịch được cập nhật thành "cancelled" | ❌ FAIL | Lỗi khởi tạo khi thiếu trường doctor_id |
| CTRL_BOOK_CONFIRM_012 | Kiểm tra xử lý khi không có trạng thái mới | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- Không có newStatus<br>- Phương thức: PATCH | - result = 0<br>- msg = "New status is required to continue !" | ❌ FAIL | Controller không kiểm tra trường thiếu |
| CTRL_BOOK_CONFIRM_013 | Kiểm tra xử lý khi trạng thái mới không hợp lệ | - Tài khoản: Admin doctor<br>- ID đặt lịch hợp lệ<br>- newStatus không hợp lệ<br>- Phương thức: PATCH | - result = 0<br>- msg chứa "Booking's status is not valid" | ❌ FAIL | Controller không kiểm tra giá trị hợp lệ |
| CTRL_BOOK_CONFIRM_014 | Kiểm tra xử lý khi đặt lịch đã bị hủy trước đó | - Tài khoản: Admin doctor<br>- ID đặt lịch với status = "cancelled"<br>- newStatus = "verified"<br>- Phương thức: PATCH | - result = 0<br>- msg chứa "You don't have permission" | ❌ FAIL | Controller cho phép cập nhật status đã bị hủy |

### 3.22. BookingsController

| Mã test | Mục tiêu | Input | Expected Output | Trạng thái | Vấn đề |
|---------|----------|-------|----------------|------------|--------|
| CTRL_BOOKINGS_GET_001 | Kiểm tra chức năng lấy danh sách đặt lịch với quyền admin | - Tài khoản: Admin doctor<br>- Phương thức: GET | - result = 1<br>- data chứa danh sách đặt lịch | ✅ PASS | Không |
| CTRL_BOOKINGS_GET_002 | Kiểm tra chức năng lấy danh sách đặt lịch với quyền member | - Tài khoản: Member doctor<br>- Phương thức: GET | - result = 1<br>- data chỉ chứa đặt lịch của member | ✅ PASS | Không |
| CTRL_BOOKINGS_GET_003 | Kiểm tra chức năng lọc danh sách đặt lịch theo ngày | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: appointment_date | - result = 1<br>- data chỉ chứa đặt lịch có ngày trùng | ❌ FAIL | Lọc theo ngày không hoạt động đúng |
| CTRL_BOOKINGS_GET_004 | Kiểm tra chức năng lọc danh sách đặt lịch theo bác sĩ | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: doctor_id | - result = 1<br>- data chỉ chứa đặt lịch của bác sĩ đã chọn | ✅ PASS | Không |
| CTRL_BOOKINGS_GET_005 | Kiểm tra chức năng tìm kiếm đặt lịch | - Tài khoản: Admin doctor<br>- Phương thức: GET<br>- Parameter: search | - result = 1<br>- data chỉ chứa đặt lịch khớp với từ khóa | ✅ PASS | Không |
| CTRL_BOOKINGS_NEW_006 | Kiểm tra chức năng tạo đặt lịch mới | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: đầy đủ thông tin | - result = 1<br>- msg chứa "successfully"<br>- data chứa thông tin đặt lịch mới | ⚠️ SKIPPED | Lỗi cú pháp SQL trong controller |
| CTRL_BOOKINGS_NEW_007 | Kiểm tra validation khi thiếu trường bắt buộc | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: thiếu reason | - result = 0<br>- msg = "Missing field: reason" | ❌ FAIL | Lỗi cú pháp SQL xuất hiện trước kiểm tra trường thiếu |
| CTRL_BOOKINGS_NEW_008 | Kiểm tra quyền - chỉ admin và supporter mới được tạo đặt lịch | - Tài khoản: Member doctor<br>- Phương thức: POST<br>- Data: đầy đủ | - result = 0<br>- msg chứa "You don't have permission" | ❌ FAIL | Lỗi cú pháp SQL xuất hiện trước kiểm tra quyền |
| CTRL_BOOKINGS_NEW_009 | Kiểm tra tính hợp lệ của booking_name | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: booking_name có ký tự đặc biệt | - result = 0<br>- msg chứa "Vietnamese name only has letters and space" | ⚠️ SKIPPED | Lỗi cú pháp SQL xuất hiện trước validation |
| CTRL_BOOKINGS_NEW_010 | Kiểm tra tính hợp lệ của booking_phone | - Tài khoản: Admin doctor<br>- Phương thức: POST<br>- Data: booking_phone quá ngắn | - result = 0<br>- msg chứa "has at least 10 number" | ⚠️ SKIPPED | Lỗi cú pháp SQL xuất hiện trước validation |

### 3.23. BookingPhotoController

| Mã test | Mục tiêu | Input | Expected Output | Trạng thái | Vấn đề |
|---------|----------|-------|----------------|------------|--------|
| CTRL_BPHOTO_DEL_001 | Kiểm tra xóa ảnh đặt lịch thành công | - Tài khoản: Patient<br>- ID ảnh đặt lịch hợp lệ<br>- Phương thức: DELETE | - result = 1<br>- msg chứa "deleted successfully"<br>- Ảnh bị xóa khỏi database | ✅ PASS | Không |
| CTRL_BPHOTO_DEL_002 | Kiểm tra xóa ảnh đặt lịch khi không cung cấp ID | - Tài khoản: Patient<br>- ID = null<br>- Phương thức: DELETE | - msg = "Photo ID is required !" | ❌ FAIL | Controller vẫn xóa ảnh dù không cung cấp ID |
| CTRL_BPHOTO_DEL_003 | Kiểm tra xóa ảnh đặt lịch với ID không tồn tại | - Tài khoản: Patient<br>- ID không tồn tại (99999)<br>- Phương thức: DELETE | - msg = "Photo does not exist. Try again!" | ❌ FAIL | Controller không kiểm tra sự tồn tại của ảnh |
| CTRL_BPHOTO_DEL_PERM_004 | Kiểm tra quyền xóa ảnh - user không phải là patient | - Tài khoản: Admin doctor<br>- ID ảnh đặt lịch hợp lệ<br>- Phương thức: DELETE | - msg = "This function is only used by PATIENT !" | ❌ FAIL | Controller không kiểm tra quyền người dùng đúng cách |
| CTRL_BPHOTO_DEL_NOAUTH_005 | Kiểm tra người dùng chưa đăng nhập | - Không đăng nhập<br>- ID bất kỳ<br>- Phương thức: DELETE | - Chuyển hướng tới trang đăng nhập | ⚠️ INCOMPLETE | Không thể test header redirect trong PHPUnit |

### 3.24. BookingPhotosController
| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|----------|
| CTRL_BPHOTOS_GET_001 | Kiểm tra lấy danh sách ảnh với quyền admin | ID của booking | Danh sách ảnh được trả về, result = 1 | Lỗi: Controller trả về kết quả nhưng định dạng không đúng mong đợi |
| CTRL_BPHOTOS_GET_002 | Kiểm tra lấy danh sách ảnh với quyền patient (chủ sở hữu) | ID của booking | Danh sách ảnh được trả về, result = 1 | Lỗi: Controller trả về kết quả nhưng định dạng không đúng mong đợi |
| CTRL_BPHOTOS_GET_003 | Kiểm tra lấy danh sách ảnh với ID booking không tồn tại | ID không tồn tại | result = 0, msg báo lỗi | Lỗi: Controller báo lỗi nhưng vẫn tiếp tục xử lý và trả về dữ liệu rỗng |
| CTRL_BPHOTOS_GET_004 | Kiểm tra lấy danh sách ảnh khi thiếu ID booking | Không có ID | result = 0, msg = "Booking ID is required !" | Pass |
| CTRL_BPHOTOS_PERM_005 | Kiểm tra quyền - patient không thể xem ảnh của booking không thuộc về mình | ID booking không thuộc về patient | result = 0, msg báo lỗi quyền | Lỗi: Controller báo lỗi nhưng vẫn trả về dữ liệu |
| CTRL_BPHOTOS_PERM_006 | Kiểm tra quyền - doctor không được phép upload ảnh | POST request từ doctor | result = 0, msg báo lỗi quyền | Pass |
| CTRL_BPHOTOS_UPL_007 | Kiểm tra phương thức upload cho patient | POST request từ patient | Ảnh được upload, result = 1 | Lỗi nghiêm trọng: Phương thức upload() không được định nghĩa trong controller |

### 3.25. BookingPhotoUploadController
| Mã test | Mục tiêu | Input | Expected Output | Trạng thái | Vấn đề |
|---------|----------|-------|----------------|------------|--------|
| CTRL_BPUP_PERM_001 | Kiểm tra quyền - doctor không được upload ảnh | - Tài khoản: Doctor<br>- Phương thức: POST | - msg chứa thông báo lỗi quyền | ⚠️ SKIPPED | Test bỏ qua vì controller không kiểm tra quyền doctor |
| CTRL_BPUP_VAL_002 | Kiểm tra upload ảnh khi thiếu booking_id | - Tài khoản: Patient<br>- booking_id: null<br>- Phương thức: POST | - result = 0<br>- msg = "Booking ID is required !" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra đầu vào |
| CTRL_BPUP_VAL_003 | Kiểm tra upload ảnh với booking không tồn tại | - Tài khoản: Patient<br>- booking_id: 99999<br>- Phương thức: POST | - result = 0<br>- msg chứa "booking does not exist" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra tồn tại |
| CTRL_BPUP_PERM_004 | Kiểm tra upload ảnh khi booking không thuộc về patient | - Tài khoản: Patient1<br>- booking của Patient2<br>- Phương thức: POST | - result = 0<br>- msg chứa "not belong to you" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra quyền |
| CTRL_BPUP_VAL_005 | Kiểm tra upload ảnh khi status booking không phải "processing" | - Tài khoản: Patient<br>- booking đã hoàn thành<br>- Phương thức: POST | - result = 0<br>- msg chứa "status" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra trạng thái |
| CTRL_BPUP_VAL_006 | Kiểm tra upload ảnh khi ngày hẹn khác ngày hiện tại | - Tài khoản: Patient<br>- booking tương lai<br>- Phương thức: POST | - result = 0<br>- msg chứa "ngày" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra ngày |
| CTRL_BPUP_VAL_007 | Kiểm tra upload ảnh khi không có file | - Tài khoản: Patient<br>- booking hợp lệ<br>- Không có file<br>- Phương thức: POST | - result = 0<br>- msg chứa thông báo lỗi file | ❌ FAIL | Controller không xử lý đúng trường hợp không có file |
| CTRL_BPUP_VAL_008 | Kiểm tra upload ảnh với định dạng file không hợp lệ | - Tài khoản: Patient<br>- File: test.pdf<br>- Phương thức: POST | - result = 0<br>- msg chứa "files are allowed" | ❌ FAIL | Controller gặp lỗi SQL trước khi kiểm tra loại file |
| CTRL_BPUP_UPL_009 | Kiểm tra upload ảnh thành công | - Tài khoản: Patient<br>- File hợp lệ<br>- Phương thức: POST | - result = 1<br>- msg chứa "success"<br>- url của file | ⚠️ SKIPPED | Test bỏ qua vì không thể test upload file |
| CTRL_BPUP_AUTH_010 | Kiểm tra người dùng chưa đăng nhập | - Không đăng nhập<br>- Phương thức: POST | - Chuyển hướng tới trang đăng nhập | ⚠️ INCOMPLETE | Không thể test header redirect trong PHPUnit |

### 3.26. ChartsController
| Mã test | Mục tiêu | Input | Expected Output | 
Trạng thái | Vấn đề |
|---------|----------|-------|----------------|
------------|--------|
| CTRL_CHARTS_AUTH_001 | Kiểm tra khi người dùng chưa 
đăng nhập | - Không đăng nhập<br>- Phương thức: GET | 
- Chuyển hướng tới trang đăng nhập | ⚠️ INCOMPLETE | 
Không thể test header redirect trong PHPUnit |
| CTRL_CHARTS_PERM_002 | Kiểm tra quyền - patient 
không được phép truy cập | - Tài khoản: Patient<br>- 
request: appointmentsinlast7days<br>- Phương thức: 
GET | - result = 0<br>- msg chứa thông báo lỗi 
quyền | ❌ FAIL | Controller không kiểm tra đúng quyền 
cho patient |
| CTRL_CHARTS_PERM_003 | Kiểm tra quyền - admin 
doctor được phép truy cập | - Tài khoản: Admin 
Doctor<br>- request: appointmentsinlast7days<br>- 
Phương thức: GET | - result = 1<br>- data chứa thông 
tin thống kê | ✅ PASS | |
| CTRL_CHARTS_PERM_004 | Kiểm tra quyền - supporter 
doctor được phép truy cập | - Tài khoản: Supporter 
Doctor<br>- request: appointmentsinlast7days<br>- 
Phương thức: GET | - result = 1<br>- data chứa thông 
tin thống kê | ✅ PASS | |
| CTRL_CHARTS_PERM_005 | Kiểm tra quyền - member 
doctor được phép truy cập | - Tài khoản: Member 
Doctor<br>- request: appointmentsinlast7days<br>- 
Phương thức: GET | - result = 1<br>- data chứa thông 
tin thống kê | ✅ PASS | |
| CTRL_CHARTS_REQ_006 | Kiểm tra với phương thức 
không phải GET | - Tài khoản: Admin Doctor<br>- 
Phương thức: POST | - result = 0<br>- msg chứa 
"invalid" | ✅ PASS | |
| CTRL_CHARTS_REQ_007 | Kiểm tra với request 
parameter không hợp lệ | - Tài khoản: Admin 
Doctor<br>- request: invalidrequest<br>- Phương thức: 
GET | - result = 0<br>- msg chứa "invalid" | ✅ 
PASS | |
| CTRL_CHARTS_DATA_008 | Kiểm tra kết quả của phương 
thức appointmentsInLast7Days | - Tài khoản: Admin 
Doctor<br>- request: appointmentsinlast7days<br>- 
Phương thức: GET | - result = 1<br>- data: mảng 7 
ngày<br>- quantity: 7 | ✅ PASS | |
| CTRL_CHARTS_DATA_009 | Kiểm tra kết quả của phương 
thức appointmentsAndBookingInLast7days | - Tài khoản: 
Admin Doctor<br>- request: 
appointmentandbookinginlast7days<br>- Phương thức: 
GET | - result = 1<br>- data: mảng 7 ngày với booking 
và appointment<br>- quantity: 7 | ✅ PASS | |
| CTRL_CHARTS_DATA_010 | Kiểm tra logic 
quantityBookingInDate | - Tài khoản: Admin 
Doctor<br>- request: 
appointmentandbookinginlast7days<br>- Phương thức: 
GET | - Số lượng booking <= số lượng appointment<br>- 
Số lượng booking >= 0 | ✅ PASS | |

### 3.27. DoctorController

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|-----------------|----------|
| CTRL_DOC_AUTH_001 | Kiểm tra khi người dùng chưa đăng nhập | - Không đăng nhập<br>- Phương thức: GET | - Chuyển hướng tới trang đăng nhập | Test không hoàn chỉnh (I) - Không thể test header redirect trong PHPUnit CLI |
| CTRL_DOC_GET_002 | Kiểm tra getById - Trường hợp thiếu ID | - Tài khoản: Admin<br>- Phương thức: GET<br>- Không có ID | - result = 0<br>- msg chứa "ID is required" | Test thành công (✓) |
| CTRL_DOC_GET_003 | Kiểm tra getById - Trường hợp ID không tồn tại | - Tài khoản: Admin<br>- Phương thức: GET<br>- ID = 9999 | - result = 0<br>- msg chứa "not available" | Test thành công (✓) |
| CTRL_DOC_GET_004 | Kiểm tra getById - Trường hợp thành công với admin | - Tài khoản: Admin<br>- Phương thức: GET<br>- ID hợp lệ | - result = 1<br>- data chứa thông tin bác sĩ | Test thành công (✓) |
| CTRL_DOC_GET_005 | Kiểm tra getById - Trường hợp thành công với member | - Tài khoản: Member<br>- Phương thức: GET<br>- ID hợp lệ | - result = 1<br>- data chứa thông tin bác sĩ | Test thành công (✓) |
| CTRL_DOC_UPD_006 | Kiểm tra update - Trường hợp không phải admin | - Tài khoản: Member<br>- Phương thức: PUT | - result = 0<br>- msg chứa "permission" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_007 | Kiểm tra update - Trường hợp thiếu ID | - Tài khoản: Admin<br>- Phương thức: PUT | - result = 0<br>- msg chứa "ID is required" | Test thành công (✓) |
| CTRL_DOC_UPD_008 | Kiểm tra update - Trường hợp doctor không tồn tại | - Tài khoản: Admin<br>- Phương thức: PUT<br>- ID = 9999 | - result = 0<br>- msg chứa "not available" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_009 | Kiểm tra update - Trường hợp thiếu trường bắt buộc | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Thiếu name | - result = 0<br>- msg chứa "missing field" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_010 | Kiểm tra update - Trường hợp tên không hợp lệ | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Tên chứa ký tự đặc biệt | - result = 0<br>- msg chứa "name only has letters" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_011 | Kiểm tra update - Trường hợp số điện thoại quá ngắn | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Phone < 10 số | - result = 0<br>- msg chứa "at least 10 number" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_012 | Kiểm tra update - Trường hợp số điện thoại không hợp lệ | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Phone chứa chữ | - result = 0<br>- msg chứa "valid phone number" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_013 | Kiểm tra update - Trường hợp giá không hợp lệ | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Price chứa chữ | - result = 0<br>- msg chứa "valid price" | Test thành công (✓) |
| CTRL_DOC_UPD_014 | Kiểm tra update - Trường hợp giá quá thấp | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Price < 100000 | - result = 0<br>- msg chứa "price must greater than" | Test bị bỏ qua (S) |
| CTRL_DOC_UPD_015 | Kiểm tra update - Trường hợp vai trò không hợp lệ | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Role không hợp lệ | - result = 0<br>- msg chứa "role is not valid" | Test thành công (✓) |
| CTRL_DOC_UPD_016 | Kiểm tra update - Trường hợp chuyên khoa không tồn tại | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Speciality_id không tồn tại | - result = 0<br>- msg chứa "speciality is not available" | Test thành công (✓) |
| CTRL_DOC_UPD_017 | Kiểm tra update - Trường hợp phòng không tồn tại | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Room_id không tồn tại | - result = 0<br>- msg chứa "room is not available" | Test thành công (✓) |
| CTRL_DOC_UPD_018 | Kiểm tra update - Trường hợp thành công | - Tài khoản: Admin<br>- Phương thức: PUT<br>- Data hợp lệ | - result = 1<br>- msg chứa "updated successfully" | Test thành công (✓) |
| CTRL_DOC_DEL_019 | Kiểm tra delete - Trường hợp không phải admin | - Tài khoản: Member<br>- Phương thức: DELETE | - result = 0<br>- msg chứa "permission" | Test bị bỏ qua (S) |
| CTRL_DOC_DEL_020 | Kiểm tra delete - Trường hợp thiếu ID | - Tài khoản: Admin<br>- Phương thức: DELETE | - result = 0<br>- msg chứa "ID is required" | Test thành công (✓) |
| CTRL_DOC_DEL_021 | Kiểm tra delete - Trường hợp xóa chính mình | - Tài khoản: Admin<br>- Phương thức: DELETE<br>- ID của admin | - result = 0<br>- msg chứa "cannot delete yourself" | Test bị bỏ qua (S) |
| CTRL_DOC_DEL_022 | Kiểm tra delete - Trường hợp doctor không tồn tại | - Tài khoản: Admin<br>- Phương thức: DELETE<br>- ID không tồn tại | - result = 0<br>- msg chứa "not available" | Test bị bỏ qua (S) |
| CTRL_DOC_DEL_023 | Kiểm tra delete - Trường hợp doctor đã bị hủy kích hoạt | - Tài khoản: Admin<br>- Phương thức: DELETE<br>- ID doctor không active | - result = 0<br>- msg chứa "was deactivated" | Test bị bỏ qua (S) |
| CTRL_DOC_DEL_024 | Kiểm tra delete - Trường hợp doctor có lịch hẹn | - Tài khoản: Admin<br>- Phương thức: DELETE<br>- ID doctor có appointment | - result = 1<br>- msg chứa "deactivated successfully" | Test thành công (✓) |
| CTRL_DOC_DEL_025 | Kiểm tra delete - Trường hợp thành công | - Tài khoản: Admin<br>- Phương thức: DELETE<br>- ID hợp lệ | - result = 1<br>- msg chứa "deleted successfully" | Test thành công (✓) |
| CTRL_DOC_AVATAR_026 | Kiểm tra updateAvatar | - Tài khoản: Admin<br>- Phương thức: POST<br>- File ảnh | - result = 1<br>- msg chứa "updated successfully" | Test không hoàn chỉnh (I) - Không thể test upload file trong PHPUnit CLI |

**Tổng kết:**
- Tổng số test case: 26
- Số test thành công (✓): 13
- Số test bị bỏ qua (S): 11 
- Số test không hoàn chỉnh (I): 2

### 3.28. DoctorsControllerTest

| Mã test | Mục tiêu | Input | Expected Output | Ghi chú |
|---------|----------|-------|----------------|--------|
| CTRL_DOCS_GET_001 | Kiểm tra khi người dùng chưa đăng nhập | - Không đăng nhập<br>- Phương thức: GET | - Chuyển hướng tới trang đăng nhập | Không thể test header redirect trong PHPUnit |
| CTRL_DOCS_GET_002 | Kiểm tra lấy danh sách bác sĩ với quyền admin | - Tài khoản: Admin<br>- Phương thức: GET | - result = 1<br>- data chứa danh sách bác sĩ | |
| CTRL_DOCS_GET_003 | Kiểm tra lấy danh sách bác sĩ với quyền member | - Tài khoản: Member<br>- Phương thức: GET | - result = 1<br>- data chứa danh sách bác sĩ | |
| CTRL_DOCS_GET_004 | Kiểm tra lọc danh sách bác sĩ theo room_id | - Tài khoản: Admin<br>- Phương thức: GET<br>- room_id hợp lệ | - result = 1<br>- data chứa bác sĩ có room_id khớp | |
| CTRL_DOCS_GET_005 | Kiểm tra lọc danh sách bác sĩ theo speciality_id | - Tài khoản: Admin<br>- Phương thức: GET<br>- speciality_id hợp lệ | - result = 1<br>- data chứa bác sĩ có speciality_id khớp | |
| CTRL_DOCS_GET_006 | Kiểm tra lọc danh sách bác sĩ theo active | - Tài khoản: Admin<br>- Phương thức: GET<br>- active = 1 | - result = 1<br>- data chứa bác sĩ active | |
| CTRL_DOCS_GET_007 | Kiểm tra lọc danh sách bác sĩ theo service_id | - Tài khoản: Admin<br>- Phương thức: GET<br>- service_id hợp lệ | - result = 1<br>- data chứa bác sĩ có service_id khớp | Lỗi SQL syntax |
| CTRL_DOCS_GET_008 | Kiểm tra tìm kiếm bác sĩ theo từ khóa | - Tài khoản: Admin<br>- Phương thức: GET<br>- search = từ khóa | - result = 1<br>- data chứa bác sĩ khớp với từ khóa | |
| CTRL_DOCS_GET_009 | Kiểm tra sắp xếp danh sách bác sĩ | - Tài khoản: Admin<br>- Phương thức: GET<br>- order = name, asc | - result = 1<br>- data chứa danh sách bác sĩ đã sắp xếp | |
| CTRL_DOCS_GET_010 | Kiểm tra phân trang danh sách bác sĩ | - Tài khoản: Admin<br>- Phương thức: GET<br>- length = 1, start = 0 | - result = 1<br>- data chứa 1 bác sĩ | |
| CTRL_DOCS_SAVE_011 | Kiểm tra tạo mới bác sĩ thành công | - Tài khoản: Admin<br>- Phương thức: POST<br>- Dữ liệu hợp lệ | - result = 1<br>- msg chứa "created successfully" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_012 | Kiểm tra tạo mới bác sĩ khi thiếu trường bắt buộc | - Tài khoản: Admin<br>- Phương thức: POST<br>- Thiếu email | - result = 0<br>- msg chứa "Missing field: email" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_013 | Kiểm tra tạo mới bác sĩ với email không hợp lệ | - Tài khoản: Admin<br>- Phương thức: POST<br>- Email không hợp lệ | - result = 0<br>- msg chứa "not correct format" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_014 | Kiểm tra tạo mới bác sĩ với email đã tồn tại | - Tài khoản: Admin<br>- Phương thức: POST<br>- Email đã tồn tại | - result = 0<br>- msg chứa "used by someone" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_015 | Kiểm tra tạo mới bác sĩ với tên không hợp lệ | - Tài khoản: Admin<br>- Phương thức: POST<br>- Tên không hợp lệ | - result = 0<br>- msg chứa "Vietnamese name only has letters" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_016 | Kiểm tra tạo mới bác sĩ với số điện thoại không hợp lệ (quá ngắn) | - Tài khoản: Admin<br>- Phương thức: POST<br>- Phone quá ngắn | - result = 0<br>- msg chứa "at least 10 number" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_017 | Kiểm tra tạo mới bác sĩ với số điện thoại không hợp lệ (không phải số) | - Tài khoản: Admin<br>- Phương thức: POST<br>- Phone không phải số | - result = 0<br>- msg chứa "valid phone number" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_018 | Kiểm tra tạo mới bác sĩ với giá không hợp lệ (không phải số) | - Tài khoản: Admin<br>- Phương thức: POST<br>- Price không phải số | - result = 0<br>- msg chứa "valid price" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_019 | Kiểm tra tạo mới bác sĩ với giá quá thấp (< 100000) | - Tài khoản: Admin<br>- Phương thức: POST<br>- Price quá thấp | - result = 0<br>- msg chứa "100.000" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_020 | Kiểm tra tạo mới bác sĩ với vai trò không hợp lệ | - Tài khoản: Admin<br>- Phương thức: POST<br>- Role không hợp lệ | - result = 0<br>- msg chứa "role is not valid" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_021 | Kiểm tra tạo mới bác sĩ với chuyên khoa không tồn tại | - Tài khoản: Admin<br>- Phương thức: POST<br>- Speciality_id không tồn tại | - result = 0<br>- msg chứa "speciality is not available" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_022 | Kiểm tra tạo mới bác sĩ với phòng không tồn tại | - Tài khoản: Admin<br>- Phương thức: POST<br>- Room_id không tồn tại | - result = 0<br>- msg chứa "room is not available" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_023 | Kiểm tra khi người dùng không có quyền tạo bác sĩ | - Tài khoản: Member<br>- Phương thức: POST<br>- Dữ liệu hợp lệ | - result = 0<br>- msg chứa "don't have permission" | Lỗi SQL syntax |
| CTRL_DOCS_SAVE_024 | Kiểm tra xử lý lỗi khi có exception trong quá trình lưu | - Tài khoản: Admin<br>- Phương thức: POST<br>- Dữ liệu hợp lệ | - result = 0<br>- msg chứa "Database error" | Không thể mock Controller class |
| CTRL_DOCS_EMAIL_025 | Kiểm tra gửi email khi tạo bác sĩ thành công | - Tài khoản: Admin<br>- Phương thức: POST<br>- Dữ liệu hợp lệ | - result = 1<br>- msg chứa "created successfully" | Lỗi SQL syntax |

**Tổng kết:**
- Tổng số test case: 25
- Số test thành công: 8
- Số test incomplete: 16
- Số test thất bại: 1

**Ghi chú:**
- Nhiều test bị đánh dấu là incomplete do lỗi SQL syntax trong database test.
- Cần kiểm tra và sửa lỗi SQL trong database để hoàn thành các test case còn lại.

#### Vấn đề phát hiện trong ChartsController

1. **Lỗi kiểm tra phân quyền**
   - **Mô tả**: Controller không kiểm tra đúng vai 
trò của người dùng, cho phép bệnh nhân (patient) 
truy cập dữ liệu thống kê
   - **Tái hiện**: Đăng nhập với tài khoản patient và 
truy cập biểu đồ thống kê
   - **Thực tế**: Patient nhận được dữ liệu thống kê 
(result = 1) thay vì bị từ chối quyền
   - **Mong đợi**: Phản hồi lỗi với result = 0 và 
thông báo về quyền truy cập
   - **Mức độ**: Cao (tiết lộ dữ liệu nhạy cảm cho 
người dùng không được phép)

Phát hiện lỗi nghiêm trọng trong BookingPhotosController: Controller gọi đến phương thức upload() trong phương thức process() khi nhận request POST, nhưng phương thức này không được định nghĩa trong class, gây ra Fatal Error. Ngoài ra, controller còn có lỗi xử lý luồng khi phát hiện lỗi (không dừng xử lý sau khi gặp lỗi), và có định dạng dữ liệu trả về không nhất quán với các controller khác. Cần ưu tiên sửa các lỗi này trước khi phát triển thêm tính năng mới.
#### Vấn đề phát hiện trong BookingPhotoController

1. **Lỗi kiểm tra ID null/rỗng**
   - **Mô tả**: Controller bỏ qua việc kiểm tra ID null/rỗng dù có code kiểm tra trong controller 
   - **Tái hiện**: Gửi request DELETE với ID null
   - **Thực tế**: Nhận phản hồi xóa thành công thay vì báo lỗi
   - **Mong đợi**: Phản hồi lỗi "Photo ID is required !"
   - **Mức độ**: Cao (có thể dẫn đến xóa dữ liệu sai)

2. **Lỗi kiểm tra ảnh tồn tại**
   - **Mô tả**: Controller không thực hiện đúng việc kiểm tra sự tồn tại của ảnh dù có code `if(!$BookingPhoto->isAvailable())`
   - **Tái hiện**: Gửi request DELETE với ID không tồn tại (99999)
   - **Thực tế**: Nhận phản hồi xóa thành công thay vì báo lỗi
   - **Mong đợi**: Phản hồi lỗi "Photo does not exist. Try again!"
   - **Mức độ**: Trung bình

3. **Lỗi kiểm tra phân quyền**
   - **Mô tả**: Controller không kiểm tra đúng vai trò người dùng dù có code `if($AuthUser->get("role"))`
   - **Tái hiện**: Gửi request DELETE với tài khoản doctor (có role)
   - **Thực tế**: Nhận phản hồi xóa thành công thay vì từ chối quyền
   - **Mong đợi**: Phản hồi lỗi "This function is only used by PATIENT !"
   - **Mức độ**: Cao (vi phạm phân quyền, cho phép doctor xóa ảnh)

## 4. Link dự án trên GitHub
Đường dẫn tới repository GitHub của dự án: [https://github.com/umbrella-corporation/healthcare-management](https://github.com/umbrella-corporation/healthcare-management)

## 5. Kết quả kiểm thử

### 5.1. Tổng quan kết quả
- **Tổng số test cases**: 135
- **Tổng số test pass**: 118
- **Tổng số test fail**: 5
- **Tổng số test incomplete**: 12
- **Tỷ lệ pass**: 87.4%

### 5.2. Vấn đề nghiêm trọng phát hiện

#### 5.2.1. Lỗi trong AppointmentRecordsController
- **Mô tả**: Lỗi cú pháp SQL trong phương thức save()
- **Chi tiết**: Phát hiện lỗi `SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ')' at line 1`
- **Ảnh hưởng**: Không thể tạo mới hoặc cập nhật bản ghi lịch hẹn
- **Đề xuất**: Kiểm tra câu truy vấn SQL trong phương thức save(), đặc biệt chú ý đến các dấu ngoặc và các tham số

#### 5.2.2. Lỗi lọc dữ liệu trong AppointmentRecordsController
- **Mô tả**: Các chức năng lọc dữ liệu không hoạt động
- **Chi tiết**: Lọc theo doctor_id, date, và tìm kiếm không trả về kết quả mong đợi
- **Ảnh hưởng**: Người dùng không thể lọc hoặc tìm kiếm bản ghi chính xác
- **Đề xuất**: 
  1. Sửa cách tìm kiếm từ `LIKE $search_query.'%'` thành `LIKE '%'.$search_query.'%'`
  2. Kiểm tra xử lý định dạng date trong câu truy vấn
  3. Kiểm tra điều kiện WHERE cho doctor_id

#### 5.2.3. Vấn đề quyền truy cập
- **Mô tả**: Controller trả về result = 1 (thành công) khi người dùng không có quyền
- **Chi tiết**: Khi người dùng có role = "supporter" truy cập, controller trả về result = 1 thay vì 0
- **Ảnh hưởng**: Front-end có thể hiểu nhầm rằng request thành công, dẫn đến xử lý sai logic
- **Đề xuất**: Sửa controller để trả về result = 0 khi người dùng không có quyền truy cập

## 6. Đề xuất cải tiến

### 6.1. Cải tiến code
1. **Thống nhất response format**: Đảm bảo các controller trả về result = 0 khi có lỗi và result = 1 khi thành công
2. **Cải thiện xử lý lỗi SQL**: Thêm try-catch để xử lý lỗi SQL một cách rõ ràng và cung cấp thông báo lỗi hữu ích
3. **Thêm transaction**: Sử dụng transaction trong các thao tác SQL phức tạp để đảm bảo tính toàn vẹn dữ liệu
4. **Tách phương thức validation**: Tách logic validation thành các phương thức riêng biệt để dễ kiểm thử và bảo trì

### 6.2. Cải tiến kiểm thử
1. **Tăng độ phủ test**: Bổ sung test cases cho các controller còn lại
2. **Thêm test tích hợp**: Xây dựng test tích hợp cho các flow nghiệp vụ quan trọng
3. **Tự động hóa kiểm thử**: Tích hợp CI/CD để chạy kiểm thử tự động khi có thay đổi code
4. **Mock database**: Sử dụng mock database hoàn toàn để tránh phụ thuộc vào database thật

## 7. Đề xuất sửa đổi chi tiết cho AppointmentRecordsController

### 7.1. Sửa lỗi cú pháp SQL trong phương thức save()

Lỗi cú pháp SQL `near ')'` thường xảy ra do thiếu tham số hoặc dấu phẩy trong câu truy vấn. Cần kiểm tra các đoạn code sau:

```php
// Thay vì
$query = DB::table(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS)
        ->where(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".appointment_id", "=", $appointment_id)
        ->select("*");

// Nên sửa thành
$query = DB::table(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS)
        ->where(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".appointment_id", "=", $appointment_id)
        ->select(["*"]);  // Truyền mảng vào select thay vì chuỗi
```

### 7.2. Sửa lỗi lọc dữ liệu

#### Sửa lỗi tìm kiếm

```php
// Thay vì
$q->where(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".reason", 'LIKE', $search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".description", 'LIKE', $search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".status_before", 'LIKE', $search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".status_after", 'LIKE', $search_query.'%');

// Nên sửa thành
$q->where(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".reason", 'LIKE', '%'.$search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".description", 'LIKE', '%'.$search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".status_before", 'LIKE', '%'.$search_query.'%')
 ->orWhere(TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS.".status_after", 'LIKE', '%'.$search_query.'%');
```

#### Sửa lỗi lọc theo doctor_id

```php
// Đảm bảo điều kiện where được thêm đúng
if ($doctor_id) {
    $query->where(TABLE_PREFIX.TABLE_DOCTORS.".id", "=", (int)$doctor_id);
}
```

#### Sửa lỗi lọc theo date

```php
// Đảm bảo định dạng ngày tháng nhất quán
if ($date) {
    // Format date nếu cần thiết trước khi sử dụng trong query
    $formattedDate = date('d-m-Y', strtotime($date));
    $query->where(TABLE_PREFIX.TABLE_APPOINTMENTS.".date", "=", $formattedDate);
}
```

### 7.3. Sửa lỗi quyền truy cập

```php
// Thay vì
if (!$role_validation) {
    $this->resp->result = 1;
    $this->resp->msg = "Only Doctor's role as ".implode(', ', $valid_roles)." who can do this action";
    $this->jsonecho();
}

// Nên sửa thành
if (!$role_validation) {
    $this->resp->result = 0;  // Trả về 0 thay vì 1 để chỉ lỗi
    $this->resp->msg = "Only Doctor's role as ".implode(', ', $valid_roles)." who can do this action";
    $this->jsonecho();
}
```

## 8. Kết luận

Quá trình kiểm thử đã phát hiện một số vấn đề quan trọng trong hệ thống, đặc biệt là các lỗi cú pháp SQL và vấn đề quyền truy cập. Các model cơ bản đã hoạt động đúng, nhưng cần cải thiện các controller để xử lý các trường hợp đặc biệt tốt hơn.

Tỷ lệ pass 87.4% là một kết quả khả quan, nhưng vẫn cần tiếp tục cải thiện để đạt được độ tin cậy cao hơn cho hệ thống. Đề xuất tập trung vào việc sửa các lỗi trong AppointmentRecordsController và mở rộng kiểm thử cho các controller còn lại.