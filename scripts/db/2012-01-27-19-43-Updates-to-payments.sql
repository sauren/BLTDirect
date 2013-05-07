ALTER TABLE `payment`  ADD COLUMN `Address_Status` VARCHAR(20) NOT NULL DEFAULT '' AFTER `Address_Result`;
ALTER TABLE `payment`  ADD COLUMN `Payer_Status` VARCHAR(20) NOT NULL DEFAULT '' AFTER `Amount`,  
	ADD COLUMN `Card_Type` VARCHAR(15) NOT NULL DEFAULT '' AFTER `Payer_Status`,  
	ADD COLUMN `Last_4_Digits` VARCHAR(4) NOT NULL DEFAULT '' AFTER `Card_Type`;
ALTER TABLE `orders`  ADD COLUMN `Basket_ID` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `Confirmed_Notes`;