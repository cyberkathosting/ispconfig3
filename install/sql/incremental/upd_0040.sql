
-- Removal of the domain module
UPDATE sys_user SET startmodule = 'dashboard' WHERE startmodule = 'domain';
UPDATE sys_user SET modules = replace(modules, ',domain', '') WHERE modules like '%domain%';
