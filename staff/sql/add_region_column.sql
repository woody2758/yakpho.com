-- เพิ่มคอลัมน์ region ในตาราง target_cities
ALTER TABLE target_cities 
ADD COLUMN region VARCHAR(100) DEFAULT '' AFTER city;

-- เพิ่มคอลัมน์ region ในตาราง massage_leads
ALTER TABLE massage_leads 
ADD COLUMN region VARCHAR(100) DEFAULT '' AFTER city;
