use i3crm;
insert ignore into variable (name, value) values ('environment', 'PROD');

update variable set value = 'storage@i3detroit.org' where name = 'storage_admin_email';

insert ignore into variable (name, value) values ('contact_email','contact@i3detroit.org');

