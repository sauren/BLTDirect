ALTER TABLE `orders`
  CHANGE COLUMN `Device` `DeviceBrowser` VARCHAR(120) NOT NULL DEFAULT '',
  ADD COLUMN `DeviceVersion` VARCHAR(120) NOT NULL DEFAULT '' AFTER `DeviceBrowser`,
  ADD COLUMN `DevicePlatform` VARCHAR(120) NOT NULL DEFAULT '' AFTER `DeviceVersion`;