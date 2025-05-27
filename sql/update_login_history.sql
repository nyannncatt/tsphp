ALTER TABLE login_history
ADD COLUMN logout_time DATETIME NULL AFTER login_time; 