-- Create address table for customer addresses
CREATE TABLE IF NOT EXISTS `address` (
  `addr_id` int(11) NOT NULL AUTO_INCREMENT,
  `addr_name` varchar(255) NOT NULL COMMENT 'ชื่อ-นามสกุล',
  `addr_mobile` varchar(20) NOT NULL COMMENT 'เบอร์โทรศัพท์',
  `addr_detail` text NOT NULL COMMENT 'ที่อยู่บรรทัด 1',
  `addr_detail2` varchar(255) DEFAULT NULL COMMENT 'ตำบล/แขวง อำเภอ/เขต',
  `addr_postcode` varchar(10) DEFAULT NULL COMMENT 'รหัสไปรษณีย์',
  `provinces_id` int(11) NOT NULL COMMENT 'จังหวัด',
  `addr_forword` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `addr_type` varchar(50) NOT NULL DEFAULT 'ที่บ้าน' COMMENT 'ประเภทที่อยู่',
  `addr_status` int(1) NOT NULL DEFAULT 1 COMMENT '0=ปิด 1=เปิดใช้งาน',
  `addr_del` int(1) NOT NULL DEFAULT 0 COMMENT '0=ปกติ 1=ลบ',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'เจ้าของที่อยู่',
  `addr_date` datetime NOT NULL COMMENT 'วันที่สร้าง',
  `addr_update` datetime DEFAULT NULL COMMENT 'วันที่อัพเดท',
  `save_id` int(11) NOT NULL DEFAULT 0 COMMENT 'ผู้สร้าง',
  `update_id` int(11) NOT NULL DEFAULT 0 COMMENT 'ผู้อัพเดท',
  PRIMARY KEY (`addr_id`),
  KEY `user_id` (`user_id`),
  KEY `provinces_id` (`provinces_id`),
  KEY `addr_status` (`addr_status`),
  KEY `addr_del` (`addr_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางที่อยู่ลูกค้า';
