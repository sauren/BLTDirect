update `payment_method` set `Method`='Manual PDQ Payment' where `Reference`='pdq';
INSERT INTO `settings` (`Property`, `Value`, `Description`, `Type`) VALUES ('payments_use_pdq', 'true', 'Should telesales portal be allowed to take payments manually via PDQ machine', 'boolean');
