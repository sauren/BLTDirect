ALTER TABLE `customer_basket`
  MODIFY COLUMN `Prefix` ENUM('W','U','T','L','M') NOT NULL DEFAULT 'W';