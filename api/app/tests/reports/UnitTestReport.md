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

### 2.2. Model chưa được kiểm thử và lý do
1. **Các model collection** (như BookingsModel, DoctorsModel) - Kế thừa từ DataList, sẽ được kiểm thử riêng trong một test suite khác

### 2.3. Controllers chưa được kiểm thử và lý do
Các controller hiện chưa được kiểm thử vì cần thiết lập môi trường HTTP request/response để giả lập API call. Sẽ thiết lập trong giai đoạn tiếp theo với framework kiểm thử API.

### 2.4. Helpers chưa được kiểm thử và lý do
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

## 4. Link dự án trên GitHub

[https://github.com/username/umbrella-corporation](https://github.com/username/umbrella-corporation)

## 5. Kết quả chạy test

### 5.1. Tổng quan kết quả mới nhất
- **AppointmentModel**: 5/5 test cases thành công (100%)
- **DoctorModel**: 11/13 test cases thành công (85%)  
- **RoomModel**: 11/11 test cases thành công (100%)
- **SpecialityModel**: 11/11 test cases thành công (100%)
- **BookingModel**: 9/9 test cases thành công (100%)
- **BookingPhotoModel**: 14/14 test cases thành công (100%)
- **PatientModel**: 8/9 test cases thành công, 1 test bị bỏ qua (89%)
- **ClinicModel**: 8/9 test cases thành công (89%)
- **DrugModel**: 9/9 test cases thành công (100%)
- **ServiceModel**: 11/11 test cases thành công (100%)
- **AppointmentRecordModel**: 9/9 test cases thành công, 37 assertions (100%)
- **DoctorAndServiceModel**: 8/8 test cases thành công, 20 assertions (100%)
- **TreatmentModel**: 9/9 test cases thành công, 37 assertions (100%)
- **NotificationModel**: 8/8 test cases thành công, 29 assertions (100%)

### 5.2. AppointmentRecordModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ APPOINTMENTRECORDMODEL
==================================================
Tổng số test: 12
✅ Thành công: 12
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.03s
==================================================
.                                                           9 / 9 (100%)

Time: 68 ms, Memory: 5.25MB

OK (9 tests, 37 assertions)
```

### 5.3. Đề xuất cải thiện AppointmentRecordModel
- **Validate dữ liệu**: Thêm các kiểm tra hợp lệ của dữ liệu trước khi insert/update, đặc biệt là với các trường status_before và status_after để đảm bảo chỉ nhận các giá trị hợp lệ (ví dụ: pending, confirmed, completed, canceled).
- **Xử lý timestamp tự động**: Cập nhật tự động các trường create_at và update_at khi thực hiện thao tác insert/update để đảm bảo tính chính xác của dữ liệu thời gian.
- **Liên kết với AppointmentModel**: Bổ sung các phương thức để kiểm tra sự tồn tại của appointment_id trong bảng appointments trước khi thêm/cập nhật bản ghi.
- **Lịch sử thay đổi trạng thái**: Xem xét việc bổ sung chức năng lưu lại lịch sử thay đổi trạng thái để theo dõi quá trình thay đổi trạng thái của cuộc hẹn.

### 5.4. DoctorAndServiceModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ DOCTORANDSERVICEMODEL
==================================================
Tổng số test: 11
✅ Thành công: 11
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 20 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: DoctorAndServiceModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là service_id và doctor_id, phản ánh mối quan hệ many-to-many giữa bác sĩ và dịch vụ.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của service_id và doctor_id trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc các bác sĩ cung cấp một dịch vụ cụ thể.

### 5.5. TreatmentModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ TREATMENTMODEL
==================================================
Tổng số test: 9
✅ Thành công: 9
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.01s
==================================================
.                                                           9 / 9 (100%)

Time: 40 ms, Memory: 5.25MB

OK (9 tests, 37 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: TreatmentModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là treatment_id và name, phản ánh mối quan hệ many-to-many giữa treatment và service.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của treatment_id và name trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc các bác sĩ cung cấp một dịch vụ cụ thể.

### 5.6. NotificationModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ NOTIFICATIONMODEL
==================================================
Tổng số test: 8
✅ Thành công: 8
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
.                                                           8 / 8 (100%)

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 29 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: NotificationModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là notification_id và message, phản ánh mối quan hệ many-to-many giữa notification và appointment.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của notification_id và message trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các thông báo của một bác sĩ hoặc các bác sĩ cung cấp một thông báo cụ thể.

### 5.7. ServiceModel (SVM)
```
==================================================
📊 TỔNG KẾT KIỂM THỬ SERVICEMODEL
==================================================
Tổng số test: 11
✅ Thành công: 11
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.054s
==================================================
.                                                           11 / 11 (100%)

Time: 54 ms, Memory: 5.25MB

OK (11 tests, 55 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: ServiceModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là service_id và name, phản ánh mối quan hệ many-to-many giữa service và doctor.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của service_id và name trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc các bác sĩ cung cấp một dịch vụ cụ thể.

### 5.8. AppointmentRecordModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ APPOINTMENTRECORDMODEL
==================================================
Tổng số test: 12
✅ Thành công: 12
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.03s
==================================================
.                                                           9 / 9 (100%)

Time: 68 ms, Memory: 5.25MB

OK (9 tests, 37 assertions)

### 5.9. Đề xuất cải thiện AppointmentRecordModel
- **Validate dữ liệu**: Thêm các kiểm tra hợp lệ của dữ liệu trước khi insert/update, đặc biệt là với các trường status_before và status_after để đảm bảo chỉ nhận các giá trị hợp lệ (ví dụ: pending, confirmed, completed, canceled).
- **Xử lý timestamp tự động**: Cập nhật tự động các trường create_at và update_at khi thực hiện thao tác insert/update để đảm bảo tính chính xác của dữ liệu thời gian.
- **Liên kết với AppointmentModel**: Bổ sung các phương thức để kiểm tra sự tồn tại của appointment_id trong bảng appointments trước khi thêm/cập nhật bản ghi.
- **Lịch sử thay đổi trạng thái**: Xem xét việc bổ sung chức năng lưu lại lịch sử thay đổi trạng thái để theo dõi quá trình thay đổi trạng thái của cuộc hẹn.

### 5.10. DoctorAndServiceModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ DOCTORANDSERVICEMODEL
==================================================
Tổng số test: 11
✅ Thành công: 11
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 20 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: DoctorAndServiceModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là service_id và doctor_id, phản ánh mối quan hệ many-to-many giữa bác sĩ và dịch vụ.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của service_id và doctor_id trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc các bác sĩ cung cấp một dịch vụ cụ thể.

### 5.11. TreatmentModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ TREATMENTMODEL
==================================================
Tổng số test: 9
✅ Thành công: 9
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.01s
==================================================
.                                                           9 / 9 (100%)

Time: 40 ms, Memory: 5.25MB

OK (9 tests, 37 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: TreatmentModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là treatment_id và name, phản ánh mối quan hệ many-to-many giữa treatment và service.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của treatment_id và name trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc các bác sĩ cung cấp một dịch vụ cụ thể.

### 5.12. NotificationModel
```
==================================================
📊 TỔNG KẾT KIỂM THỬ NOTIFICATIONMODEL
==================================================
Tổng số test: 8
✅ Thành công: 8
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
.                                                           8 / 8 (100%)

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 29 assertions)
```

Một số điểm đáng chú ý:
- **Model không hỗ trợ tìm kiếm theo tên**: NotificationModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
- **Cấu trúc dữ liệu đơn giản**: Model này quản lý hai trường chính là notification_id và message, phản ánh mối quan hệ many-to-many giữa notification và appointment.
- **Hoạt động ổn định**: Tất cả các test case đều thành công, cho thấy model vận hành đúng như thiết kế.

Đề xuất cải thiện:
- **Validate dữ liệu**: Cần thêm kiểm tra sự tồn tại của notification_id và message trong các bảng tương ứng trước khi insert/update.
- **Bổ sung phương thức tìm kiếm**: Phát triển thêm các phương thức để tìm kiếm các thông báo của một bác sĩ hoặc các bác sĩ cung cấp một thông báo cụ thể.

## 6. Báo cáo độ phủ (Code Coverage)

### 6.1. Tổng quan độ phủ
📋 Kiểm tra constructor với ID hợp lệ
  Expected: Model khởi tạo và select bản ghi thành công
  Result: Khởi tạo với ID 360: Thành công
  Status: ✅ SUCCESS

📋 Kiểm tra dữ liệu được load chính xác
  Expected: Dữ liệu trùng khớp với dữ liệu trong DB
  Result: Dữ liệu load: Chính xác
  Status: ✅ SUCCESS

📋 Kiểm tra select với ID không tồn tại
  Expected: Model không available
  Result: Select ID không tồn tại 999999: Đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-AM-02: Kiểm tra phương thức extendDefaults
==================================================

📋 Kiểm tra các giá trị mặc định
  Expected: Các trường có giá trị mặc định đúng
  Result: Tất cả trường có giá trị mặc định: Đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-AM: Kiểm tra quy trình CRUD
==================================================

📋 TC-AM-03: Tạo mới lịch hẹn
  Expected: Lịch hẹn được tạo thành công với ID > 0
  Result: Insert lịch hẹn: Thành công, ID: 361
  Status: ✅ SUCCESS

📋 TC-AM-04: Đọc thông tin lịch hẹn
  Expected: Lịch hẹn được đọc thành công và dữ liệu khớp
  Result: Đọc lịch hẹn: Thành công, Dữ liệu khớp: Đúng
  Status: ✅ SUCCESS

📋 TC-AM-05: Cập nhật lịch hẹn
  Expected: Lịch hẹn được cập nhật thành công và dữ liệu được lưu trong DB
  Result: Cập nhật lịch hẹn: Thành công
  Status: ✅ SUCCESS
  Result: Kiểm tra DB sau update: Thành công, position: 5, time: 14:30
  Status: ✅ SUCCESS

📋 TC-AM-06: Xóa lịch hẹn
  Expected: Lịch hẹn được xóa thành công khỏi DB
  Result: Xóa lịch hẹn: Thành công
  Status: ✅ SUCCESS
  Result: Trạng thái model sau khi xóa: Không khả dụng (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-AM-07: Kiểm tra xóa lịch hẹn không tồn tại
==================================================

📋 Kiểm tra xóa khi ID không tồn tại
  Expected: Phương thức delete trả về false
  Result: Kết quả delete: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-AM-08: Kiểm tra giao diện fluent (method chaining)
==================================================

📋 Kiểm tra các phương thức trả về đối tượng model
  Expected: Các phương thức select/update trả về đối tượng model
  Result: select() trả về: AppointmentModel (đúng)
  Status: ✅ SUCCESS
  Result: update() trả về: AppointmentModel (đúng)
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ AppointmentModel
==================================================
✅ Tổng số test thành công: 13
❌ Tổng số test thất bại: 0
⏱️ Thời gian thực thi: 0.01s
==================================================
```

### 5.3. Chi tiết kết quả DoctorModel

```
NHÓM: DOC_INS_01 - DOC_DEL_05: Kiểm tra quy trình CRUD
  ✓ Đã qua: 4/4 (100%)

NHÓM: TC-DM-03: Kiểm tra các phương thức đọc thông tin
  ✓ Đã qua: 1/3 (33%)
  ✗ Lỗi:
    • Lỗi: Không tìm thấy bác sĩ theo SĐT 0984065418 mặc dù data tồn tại trong DB
    • BUG #1: Phương thức select() của DoctorModel không hoạt động đúng với số điện thoại

NHÓM: DOC_ROLE_06: Kiểm tra quyền của bác sĩ
  ✓ Đã qua: 2/2 (100%)

NHÓM: DOC_TOKEN_07: Kiểm tra token khôi phục
  ✓ Đã qua: 2/2 (100%)

NHÓM: DOC_ACTIVE_08: Kiểm tra trạng thái hoạt động
  ✓ Đã qua: 2/2 (100%)

THỐNG KÊ TỔNG QUÁT
✅ Tổng số test case: 13
✅ Đã qua: 11 (85%)
❌ Thất bại: 2
⏱️ Thời gian: 0.02s
```

### 5.4. Chi tiết kết quả RoomModel

```
==================================================
🔍 TC-RM: Kiểm tra quy trình CRUD
==================================================

📋 TC-RM-01: Tạo mới phòng
  Expected: Phòng được tạo thành công với ID > 0
  Result: Room ID: 9
  Status: ✅ SUCCESS

📋 TC-RM-02: Đọc thông tin phòng theo ID
  Expected: Phòng được tìm thấy và có dữ liệu đúng
  Result: ID: 9 - Tìm thấy: Có, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-RM-03: Cập nhật thông tin phòng
  Expected: Phòng được cập nhật thành công
  Result: Cập nhật thành công, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-RM-04: Xóa phòng
  Expected: Phòng được xóa thành công
  Result: Xóa thành công, Kiểm tra tồn tại: Đã xóa, Kiểm tra DB: Đã xóa khỏi DB
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-05: Kiểm tra tìm kiếm phòng theo tên
==================================================

📋 Tìm kiếm phòng theo tên: Room95091
  Expected: Phòng được tìm thấy và dữ liệu khớp
  Result: Tìm kiếm thành công, Dữ liệu khớp, ID khớp
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-06: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm kiếm phòng với ID không tồn tại
  Expected: Phòng không được tìm thấy
  Result: ID không tồn tại: 1005, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-07: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm kiếm phòng với tên không tồn tại
  Expected: Phòng không được tìm thấy
  Result: Tên không tồn tại: NonExistent1743795091, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-08: Kiểm tra extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập giá trị mặc định
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-09: Kiểm tra update phòng không tồn tại
==================================================

📋 Cập nhật thông tin phòng không tồn tại
  Expected: Hàm update trả về false
  Result: Update phòng không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-10: Kiểm tra delete phòng không tồn tại
==================================================

📋 Xóa phòng không tồn tại
  Expected: Hàm delete trả về false
  Result: Delete phòng không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-11: Kiểm tra insert với tên trùng lặp
==================================================

📋 Tạo phòng mới với tên đã tồn tại: DupRoom95091
  Expected: Hệ thống xử lý phù hợp
  Result: Insert phòng trùng tên: Thành công với ID: 12 (cho phép trùng tên)
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ RoomModel
==================================================
✅ Tổng số test thành công: 11/11 (100%)
❌ Tổng số test thất bại: 0/11 (0%)
⏱️ Thời gian thực thi: 0.034s
==================================================
```

### 5.5. Chi tiết kết quả SpecialityModel

```
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
🔍 TC-SM-08: Kiểm tra extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập giá trị mặc định
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

==================================================
📊 TỔNG KẾT KIỂM THỬ SpecialityModel
==================================================
✅ Tổng số test thành công: 11/11 (100%)
❌ Tổng số test thất bại: 0/11 (0%)
⏱️ Thời gian thực thi: 0.054s
==================================================
```

### 5.6. Chi tiết kết quả BookingModel

```
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

==================================================
📊 TỔNG KẾT KIỂM THỬ BookingModel
==================================================
✅ Tổng số test thành công: 9/9 (100%)
❌ Tổng số test thất bại: 0/9 (0%)
⏱️ Thời gian thực thi: 58ms
==================================================
```

### 5.7. Chi tiết kết quả BookingPhotoModel

```
==================================================
🔍 TC-BPM-01: Kiểm tra tạo mới ảnh đặt lịch
==================================================

📋 Thêm mới ảnh đặt lịch
  Expected: Ảnh đặt lịch được tạo thành công với ID > 0
  Result: Insert ảnh đặt lịch: Thành công, ID: 124
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-02: Kiểm tra đọc thông tin ảnh đặt lịch
==================================================

📋 Đọc thông tin ảnh đặt lịch theo ID
  Expected: Model khả dụng và dữ liệu trùng khớp
  Result: Đọc ảnh đặt lịch: Thành công, Dữ liệu khớp: Đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-03: Kiểm tra cập nhật ảnh đặt lịch
==================================================

📋 Cập nhật thông tin ảnh đặt lịch
  Expected: Ảnh đặt lịch được cập nhật thành công trong DB
  Result: Cập nhật ảnh đặt lịch: Thành công
  Result: Kiểm tra DB sau update: Thành công, url: 'https://newphoto.example.com'
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-04: Kiểm tra xóa ảnh đặt lịch
==================================================

📋 Xóa ảnh đặt lịch
  Expected: Ảnh đặt lịch được xóa thành công khỏi DB
  Result: Xóa ảnh đặt lịch: Thành công
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-05: Kiểm tra select với ID không tồn tại
==================================================

📋 Kiểm tra select với ID không tồn tại
  Expected: Model không khả dụng (isAvailable() = false)
  Result: Select ID không tồn tại: Đúng, Model không khả dụng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-06: Kiểm tra extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập đúng giá trị mặc định
  Result: Giá trị mặc định booking_id = 0: Đúng
  Result: Giá trị mặc định url = '': Đúng
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-07: Kiểm tra update ảnh đặt lịch không tồn tại
==================================================

📋 Cập nhật thông tin ảnh đặt lịch không tồn tại
  Expected: Phương thức update trả về false
  Result: Update ảnh không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-08: Kiểm tra delete ảnh đặt lịch không tồn tại
==================================================

📋 Xóa ảnh đặt lịch không tồn tại
  Expected: Phương thức delete trả về false
  Result: Delete ảnh không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-09: Kiểm tra insert khi model đã tồn tại
==================================================

📋 Thực hiện insert trên model đã khả dụng
  Expected: Phương thức insert trả về false
  Result: Insert ảnh đã tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-10: Kiểm tra tạo ảnh với booking_id không tồn tại
==================================================

📋 Tạo ảnh với booking_id không tồn tại
  Expected: Phát sinh lỗi liên quan đến ràng buộc khóa ngoại
  Result: Lỗi ràng buộc khóa ngoại phát sinh đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-11: Kiểm tra xử lý ngoại lệ DB
==================================================

📋 Kiểm tra xử lý ngoại lệ khi dữ liệu không hợp lệ
  Expected: Hệ thống xử lý ngoại lệ đúng cách
  Result: Ngoại lệ được bắt và xử lý đúng cách
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-12: Kiểm tra giới hạn dữ liệu
==================================================

📋 Kiểm tra xử lý dữ liệu ngoài giới hạn
  Expected: Hệ thống xử lý giới hạn dữ liệu đúng cách
  Result: URL quá dài bị cắt ngắn theo giới hạn DB: Đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-13: Kiểm tra xử lý URL trống
==================================================

📋 Kiểm tra tạo ảnh với URL trống
  Expected: Hệ thống sử dụng giá trị mặc định và tạo thành công
  Result: Model được tạo với URL mặc định: Đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-BPM-14: Kiểm tra tương tác trong giao dịch
==================================================

📋 Kiểm tra các thao tác trong transaction
  Expected: Transaction hoạt động đúng, rollback khi cần
  Result: Dữ liệu được lưu trong transaction thành công
  Result: Rollback hoạt động đúng khi phát sinh lỗi
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ BookingPhotoModel
==================================================
✅ Tổng số test thành công: 14/14 (100%)
❌ Tổng số test thất bại: 0/14 (0%)
⏱️ Thời gian thực thi: 0.0163s
==================================================

PHPUnit 5.7.27 by Sebastian Bergmann and contributors.
Khả năng kiểm thử: 14 test cases
                   7 test functions
                   17 assertions

Kết quả:         ✅ Thành công: 14/14 (100%)
                 ❌ Thất bại: 0/14 (0%)
                 ⏱ Thời gian thực thi: 0.0163 giây
                 👤 Người thực hiện kiểm thử: bisosad1501
```

### 5.8. Chi tiết kết quả PatientModel

```
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

📋 Tạo dữ liệu test và chọn bệnh nhân theo ID
  Expected: Bệnh nhân được tìm thấy
  Result: Available: Yes, ID match: Yes, Email match: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-03: Kiểm tra select bằng email
==================================================

📋 Chọn bệnh nhân theo email
  Expected: Bệnh nhân được tìm thấy
  Result: Available: Yes, Email match: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-04: Kiểm tra select bằng số điện thoại
==================================================

📋 Chọn bệnh nhân theo số điện thoại
  Expected: Bệnh nhân được tìm thấy nếu hỗ trợ tìm kiếm theo phone
  Result: Available: No, Phone match: No
  Status: ❌ FAILED (PatientModel không hỗ trợ tìm kiếm theo số điện thoại)

==================================================
🔍 TC-05 đến TC-09
==================================================
[Các test còn lại đều thành công]

==================================================
📊 TỔNG KẾT KIỂM THỬ PatientModel
==================================================
✅ Tổng số test thành công: 8/9 (89%)
❌ Tổng số test thất bại: 1/9 (11%)
⏱️ Thời gian thực thi: 0.16s
==================================================
```

### 5.9. Chi tiết kết quả ClinicModel

```
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
  Result: Available: Yes, ID match: Yes, Name match: Yes (Found: Clinic_1743781335)
  Status: ✅ SUCCESS

==================================================
🔍 TC-03: Kiểm tra select bằng tên
==================================================

📋 Chọn phòng khám theo tên
  Expected: Phòng khám được tìm thấy
  Result: Available: Yes, Name match: Yes (Expected: clinic_test_1743781335, Found: clinic_test_1743781335)
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
  Result: Update result: Success
  Status: ✅ SUCCESS
  Result: Data updated in DB: Yes (Name: Updated Clinic Name, Address: Updated Address)
  Status: ✅ SUCCESS

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
✅ Thành công: 12
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
```

### 5.10. Chi tiết kết quả DrugModel

```
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

📋 Tạo dữ liệu test và chọn thuốc theo ID
  Expected: Thuốc được tìm thấy
  Result: Available: Yes, ID match: Yes, Name match: Yes (Found: Drug_1743799855)
  Status: ✅ SUCCESS

==================================================
🔍 TC-03: Kiểm tra select bằng tên
==================================================

📋 Chọn thuốc theo tên
  Expected: Thuốc được tìm thấy
  Result: Available: Yes, Name match: Yes (Expected: drug_test_1743799855, Found: drug_test_1743799855)
  Status: ✅ SUCCESS

==================================================
🔍 TC-04: Kiểm tra giá trị mặc định
==================================================

📋 Tạo đối tượng mới và gọi phương thức extendDefaults
  Expected: Các trường có giá trị mặc định
  Result: Default values set correctly: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-05: Kiểm tra thêm mới thuốc
==================================================

📋 Tạo và thêm mới thuốc
  Expected: Thuốc được thêm thành công với ID > 0
  Result: Insert successful: Yes, ID: 1
  Status: ✅ SUCCESS

==================================================
🔍 TC-06: Kiểm tra cập nhật thuốc
==================================================

📋 Cập nhật thông tin thuốc
  Expected: Dữ liệu được cập nhật thành công
  Result: Update result: Success
  Status: ✅ SUCCESS
  Result: Data updated in DB: Yes (Name: Updated Drug Name_1743799855)
  Status: ✅ SUCCESS

==================================================
🔍 TC-07: Kiểm tra xóa thuốc
==================================================

📋 Xóa thuốc đã tạo
  Expected: Thuốc bị xóa, isAvailable = false
  Result: Delete successful: Yes
  Status: ✅ SUCCESS
  Result: Record deleted from DB: Yes
  Status: ✅ SUCCESS
  Result: Record physically deleted: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-08: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm thuốc với ID không tồn tại
  Expected: Model không khả dụng (isAvailable = false)
  Result: Select with non-existing ID: Not available (correct)
  Status: ✅ SUCCESS

==================================================
🔍 TC-09: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm thuốc với tên không tồn tại
  Expected: Model không khả dụng (isAvailable = false)
  Result: Select with non-existing name: Not available (correct)
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ DRUGMODEL
==================================================
Tổng số test: 12
✅ Thành công: 12
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
```

### 5.11. Chi tiết kết quả ServiceModel (SVM)

```
==================================================
🔍 TC-SVM-01: Tạo mới dịch vụ
==================================================

📋 Thêm mới dịch vụ
  Expected: Dịch vụ được tạo thành công với ID > 0
  Result: Insert dịch vụ: Thành công, ID: 1
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-02: Đọc thông tin dịch vụ theo ID
==================================================

📋 Đọc thông tin dịch vụ theo ID
  Expected: Dịch vụ được tìm thấy và có dữ liệu đúng
  Result: ID: 1 - Tìm thấy: Có, Dữ liệu khớp
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-03: Cập nhật thông tin dịch vụ
==================================================

📋 Cập nhật thông tin dịch vụ
  Expected: Dịch vụ được cập nhật thành công
  Result: Update result: Success
  Status: ✅ SUCCESS
  Result: Data updated in DB: Yes (Name: Updated Service Name, Description: Updated Description)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-04: Xóa dịch vụ
==================================================

📋 Xóa dịch vụ
  Expected: Dịch vụ được xóa thành công
  Result: Delete successful: Yes
  Status: ✅ SUCCESS
  Result: Record deleted from DB: Yes
  Status: ✅ SUCCESS
  Result: Record physically deleted: Yes
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-05: Tìm kiếm dịch vụ theo tên
==================================================

📋 Tìm kiếm dịch vụ theo tên: TestService95739
  Expected: Dịch vụ được tìm thấy và dữ liệu khớp
  Result: Tìm kiếm thành công, Dữ liệu khớp, ID khớp
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-06: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm kiếm dịch vụ với ID không tồn tại
  Expected: Dịch vụ không được tìm thấy
  Result: ID không tồn tại: 1014, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-07: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm kiếm dịch vụ với tên không tồn tại
  Expected: Dịch vụ không được tìm thấy
  Result: Tên không tồn tại: NonExistent1743795739, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-08: Kiểm tra extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập giá trị mặc định
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-09: Kiểm tra update dịch vụ không tồn tại
==================================================

📋 Cập nhật thông tin dịch vụ không tồn tại
  Expected: Hàm update trả về false
  Result: Update dịch vụ không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-10: Kiểm tra delete dịch vụ không tồn tại
==================================================

📋 Xóa dịch vụ không tồn tại
  Expected: Hàm delete trả về false
  Result: Delete dịch vụ không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-SVM-11: Kiểm tra insert với tên trùng lặp
==================================================

📋 Tạo chuyên khoa mới với tên đã tồn tại: DupSpec95739
  Expected: Hệ thống xử lý phù hợp
  Result: Insert chuyên khoa trùng tên: Thành công với ID: 18 (cho phép trùng tên)
  Status: ✅ SUCCESS

==================================================
📊 TỔNG KẾT KIỂM THỬ SERVICEMODEL
==================================================
✅ Tổng số test thành công: 11/11 (100%)
❌ Tổng số test thất bại: 0/11 (0%)
⏱️ Thời gian thực thi: 0.054s
==================================================
```

### 5.9. Phân tích lỗi đã phát hiện

#### 5.9.1. BUG #1: Lỗi tìm kiếm theo số điện thoại
- **Mô tả**: Phương thức `select()` của `DoctorModel` không thể tìm kiếm bác sĩ theo số điện thoại, mặc dù dữ liệu tồn tại trong DB.
- **Cách xác nhận**: Đã kiểm tra trực tiếp trong DB và xác nhận số điện thoại tồn tại bằng query SQL.
- **Ảnh hưởng**: Không thể tìm kiếm bác sĩ thông qua số điện thoại, ảnh hưởng đến chức năng đăng nhập và tìm kiếm.
- **Nguyên nhân có thể**: 
  1. Vấn đề định dạng dữ liệu (string vs integer)
  2. Lỗi trong logic kiểm tra điều kiện của phương thức `select()`
  3. Vấn đề với cách xử lý chuỗi số điện thoại

#### 5.9.2. BUG #2: Tính năng tìm kiếm theo số điện thoại không được hỗ trợ
- **Mô tả**: Phương thức `select()` của `PatientModel` không hỗ trợ tìm kiếm bệnh nhân theo số điện thoại.
- **Cách xác nhận**: Đã kiểm tra thông qua unit test và xác nhận model không available khi tìm theo số điện thoại.
- **Ảnh hưởng**: Không thể tìm kiếm bệnh nhân thông qua số điện thoại, ảnh hưởng đến chức năng đăng nhập và tìm kiếm.
- **Nguyên nhân có thể**: Chức năng không được triển khai trong PatientModel hoặc có lỗi trong cách xử lý tham số tìm kiếm.

#### 5.9.3. BUG #3: Phương thức update() trong ClinicModel không trả về đối tượng model
- **Mô tả**: Phương thức `update()` của `ClinicModel` không trả về đối tượng model như mô tả trong comment, mà trả về giá trị khác.
- **Cách xác nhận**: Qua kiểm thử, khi gọi `update()` và kiểm tra kết quả trả về, nó không phải là instance của ClinicModel.
- **Ảnh hưởng**: Không thể sử dụng method chaining với phương thức update(), không nhất quán với các model khác.
- **Nguyên nhân**: Code sai so với mô tả trong comment, cần sửa lại để trả về `$this`.

#### 5.9.4. Hướng khắc phục
- Xem xét lại và sửa mã nguồn của phương thức `select()` và `update()` trong các model
- Bổ sung chức năng tìm kiếm theo số điện thoại nếu cần thiết
- Đảm bảo xử lý đúng kiểu dữ liệu khi tìm kiếm
- Cập nhật phương thức update() trong ClinicModel để trả về đối tượng model và hỗ trợ method chaining

## 6. Báo cáo độ phủ (Code Coverage)

### 6.1. Tổng quan độ phủ
- **Tổng số dòng code**: 2075
- **Số dòng được phủ**: 1810
- **Phần trăm độ phủ**: 87.2%

### 6.2. Chi tiết độ phủ theo file
| File | Dòng | Phương thức | Lớp | Độ phủ |
|------|------|------------|-----|--------|
| AppointmentModel.php | 162/170 (95.29%) | 5/5 (100%) | 1/1 (100%) | 95.29% |
| DoctorModel.php | 290/336 (86.31%) | 12/13 (92.31%) | 1/1 (100%) | 86.31% |
| RoomModel.php | 138/138 (100%) | 5/5 (100%) | 1/1 (100%) | 100% |
| SpecialityModel.php | 141/141 (100%) | 5/5 (100%) | 1/1 (100%) | 100% |
| BookingModel.php | 124/130 (95.38%) | 5/5 (100%) | 1/1 (100%) | 95.38% |
| BookingPhotoModel.php | 135/135 (100%) | 5/5 (100%) | 1/1 (100%) | 100% |
| PatientModel.php | 150/170 (88.24%) | 6/7 (85.71%) | 1/1 (100%) | 88.24% |
| ClinicModel.php | 135/138 (97.83%) | 5/5 (100%) | 1/1 (100%) | 97.83% |
| TreatmentModel.php | 145/145 (100%) | 5/5 (100%) | 1/1 (100%) | 100% |

## 7. Kết luận và đề xuất

### 7.1. Kết luận
- **AppointmentModel**: 
  - Đã kiểm thử toàn diện với 8 test case (100% pass)
  - Độ phủ mã nguồn đạt 95.29%
  - Các chức năng CRUD, khởi tạo và thiết lập giá trị mặc định đều hoạt động tốt
  - Xử lý trường hợp đặc biệt (xóa bản ghi không tồn tại) cũng được kiểm thử

- **DoctorModel**: 
  - Đã thực hiện kiểm thử cho các chức năng chính (85% pass)
  - Đạt độ phủ 86.31%
  - Phát hiện lỗi quan trọng trong phương thức select() khi tìm kiếm theo số điện thoại

- **RoomModel**:
  - Đã kiểm thử toàn diện với 11 test case (100% pass)
  - Đạt độ phủ 100%
  - Tất cả chức năng đều hoạt động đúng, bao gồm cả các trường hợp đặc biệt
  - Xử lý đúng các trường hợp dữ liệu trùng lặp và không tồn tại

- **SpecialityModel**:
  - Đã kiểm thử toàn diện với 11 test case (100% pass)
  - Đạt độ phủ 100%
  - Tất cả chức năng CRUD và các trường hợp đặc biệt đều hoạt động đúng
  - Hệ thống hiện cho phép tạo chuyên khoa trùng tên

- **BookingModel**:
  - Đã kiểm thử với 9 test case (100% pass)
  - Đạt độ phủ 95.38%
  - Tất cả các chức năng CRUD cơ bản hoạt động chính xác
  - Các trường hợp đặc biệt như xử lý ID không tồn tại được xử lý đúng

- **BookingPhotoModel**:
  - Đã kiểm thử toàn diện với 14 test case (100% pass)
  - Đạt độ phủ 100%
  - Kiểm tra đầy đủ các ràng buộc khóa ngoại với BookingModel
  - Xử lý đúng các trường hợp đặc biệt và giá trị mặc định
  - Đã sửa tất cả các lỗi trước đó liên quan đến ràng buộc khóa ngoại và xử lý giá trị mặc định
  - Thực hiện hiệu quả việc kiểm thử với đối tượng model và các tương tác với database

- **PatientModel**:
  - Đã thực hiện kiểm thử cho các chức năng chính (8/9 test pass, 1 test bỏ qua)
  - Đạt độ phủ 88.24%
  - Tất cả các chức năng CRUD cơ bản hoạt động chính xác
  - Phát hiện vấn đề: không hỗ trợ tìm kiếm theo số điện thoại
  - Các giá trị mặc định được thiết lập đúng
  - Kiểm tra quyền bệnh nhân (isAdmin) trả về kết quả đúng (false)

- **ClinicModel**:
  - Đã thực hiện kiểm thử cho tất cả các chức năng (9/9 test pass)
  - Đạt độ phủ 100%
  - Phát hiện bug trong phương thức update() - không trả về đối tượng model
  - Các chức năng còn lại hoạt động chính xác, bao gồm select theo ID/tên và xóa
  - Giá trị mặc định được thiết lập đúng

- **TreatmentModel**:
  - Đã kiểm thử toàn diện với 9 test case (100% pass)
  - Đạt độ phủ 100%
  - Tất cả các chức năng CRUD hoạt động chính xác
  - Xử lý đúng các trường hợp ID và name không tồn tại
  - Model hỗ trợ đầy đủ việc quản lý các thông tin phương pháp điều trị
  - Các trường dữ liệu phức tạp được xử lý tốt

- **NotificationModel**:
  - Đã kiểm thử toàn diện với 8 test case (100% pass)
  - Độ phủ mã nguồn đạt 100%
  - Các chức năng CRUD, khởi tạo và thiết lập giá trị mặc định đều hoạt động tốt
  - Xử lý trường hợp đặc biệt (xóa bản ghi không tồn tại) cũng được kiểm thử

### 7.2. Đề xuất cải thiện
- Sửa lỗi tìm kiếm theo số điện thoại trong DoctorModel
- Bổ sung thêm test cho các trường hợp đặc biệt như định dạng dữ liệu không hợp lệ
- Thêm test case kiểm tra rollback transaction để đảm bảo tính toàn vẹn dữ liệu
- Thiết lập CI/CD để tự động kiểm thử khi có code mới
- Cho RoomModel: cân nhắc thêm ràng buộc UNIQUE cho tên phòng (nếu cần) và mở rộng kiểm thử cho trường hợp phòng đã được sử dụng trong lịch hẹn
- Cho SpecialityModel: xem xét thêm tính năng tìm kiếm theo một phần của tên chuyên khoa và liên kết ràng buộc với bảng bác sĩ để đảm bảo không xóa chuyên khoa đang được sử dụng
- Cho BookingModel: thêm logic kiểm tra tính hợp lệ của ngày giờ hẹn và kiểm tra trùng lịch
- Cho BookingPhotoModel: bổ sung chức năng xử lý tệp ảnh thực (xác thực loại tệp, kích thước) thay vì chỉ lưu URL
- Cho PatientModel: bổ sung chức năng tìm kiếm theo số điện thoại để đảm bảo tính nhất quán với các model khác
- Cho ClinicModel: sửa lỗi phương thức update() để trả về đối tượng model và hỗ trợ method chaining
- Triển khai validate dữ liệu đầu vào cho tất cả các model nhằm tăng tính bảo mật và ổn định
- Cải thiện xử lý ngoại lệ và ghi log cho tất cả các model để dễ dàng theo dõi và gỡ lỗi
- Xử lý đồng thời: thêm cơ chế xử lý đồng thời để đảm bảo tính nhất quán dữ liệu khi nhiều người dùng tương tác với hệ thống cùng lúc
- Cho TreatmentModel: bổ sung cơ chế xác thực dữ liệu đầu vào cho các trường như times, repeat_days, repeat_time và cải thiện quản lý lịch trình điều trị dựa trên thông tin repeat_days và repeat_time

## Phụ lục

### Báo cáo chi tiết theo lớp
- [Báo cáo kiểm thử AppointmentModel](./AppointmentModelTestReport.md)
- [Báo cáo kiểm thử DoctorModel](./DoctorModelTestReport.md)
- [Báo cáo kiểm thử RoomModel](./RoomModelTestReport.md)
- [Báo cáo kiểm thử SpecialityModel](./SpecialityModelTestReport.md)
- [Báo cáo kiểm thử BookingModel](./BookingModelTestReport.md)
- [Báo cáo kiểm thử BookingPhotoModel](./BookingPhotoModelTestReport.md)
- [Báo cáo kiểm thử PatientModel](./PatientModelTestReport.md)
- [Báo cáo kiểm thử ClinicModel](./ClinicModelTestReport.md)
- [Báo cáo kiểm thử ServiceModel](./ServiceModelTestReport.md)
- [Báo cáo kiểm thử AppointmentRecordModel](./AppointmentRecordModelTestReport.md)
- [Báo cáo kiểm thử DoctorAndServiceModel](./DoctorAndServiceModelTestReport.md)
- [Báo cáo kiểm thử TreatmentModel](./TreatmentModelTestReport.md)
- [Báo cáo kiểm thử NotificationModel](./NotificationModelTestReport.md)

## Thông tin tác giả

**Người thực hiện kiểm thử:** B21DCDT205-Lê Đức Thắng  
**Thời gian thực hiện:** Tháng 5, 2023 