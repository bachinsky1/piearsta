ALTER TABLE `mod_transactions` ADD COLUMN `payment_nonce` VARCHAR(255) NULL DEFAULT NULL ;

ALTER TABLE `mod_orders` ADD COLUMN `payment_reference` VARCHAR(255) NULL DEFAULT NULL ;

ALTER TABLE `mod_transactions` ADD COLUMN `refunded_amount` DECIMAL(10,2) NULL DEFAULT NULL ;

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
('mirros_payment_in_process_page','Mirrors','Payment in process page','','selcat',0,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailSubject_9','Reservation','Subject(status: payment in process)','','text',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','Maksājumu pagaidām nēesam saņēmuši',0);

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailBody_9','Reservation','Email body(Status: payment in process)','','textarea',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','<p>   Šis e-pasts ir izveidots automātiski, lūdzam uz to neatbildēt.</p>
<p>
Tavs pieteikums ir reģistrēts un tiks apstrādāts 24 stundu laikā.{clinic_zvani}</p>
<p> Maksājumu pagaidām nēesam saņēmuši.</p>
<p>
Pieraksta laiks: {start_time}</p>
<p>
Speciālists: {doctor_name}</p>
<p>
Iestāde: {clinic_name}</p>
{clinic_address}
{clinic_phone}
{clinic_email}
<p>
Pakalpojums: {service_name}</p>
<p>
Pieraksta statuss: {status}</p>
<hr />
<p>
{message}</p>',0);

ALTER TABLE `mod_transactions` ADD COLUMN `request_response` TEXT NULL DEFAULT NULL ;

ALTER TABLE `mod_orders` ADD COLUMN `payment_nonce` VARCHAR(255) NULL DEFAULT NULL ;
ALTER TABLE `mod_orders` ADD COLUMN `payment_description` VARCHAR(255) NULL DEFAULT NULL ;
ALTER TABLE `mod_transactions` ADD COLUMN `payment_status` VARCHAR(255) NULL DEFAULT NULL ;

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailSubject_10','Reservation','Subject(status: payment still in process)','','text',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','Maksājumu vēl aizvien nēesam saņēmuši',0);

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailBody_10','Reservation','Email body(Status: payment still in process)','','textarea',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','<p>   Šis e-pasts ir izveidots automātiski, lūdzam uz to neatbildēt.</p>

<p>Vēlamies informēt, ka vēl aizvien nēesam saņēmuši Jūsu maksājumu.</p>
<p>
Pieraksta laiks: {start_time}</p>
<p>
Speciālists: {doctor_name}</p>
<p>
Iestāde: {clinic_name}</p>
{clinic_address}
{clinic_phone}
{clinic_email}
<p>
Pakalpojums: {service_name}</p>
<p>
Pieraksta statuss: {status}</p>
<hr />
<p>
{message}</p>',0);

ALTER TABLE `mod_transactions` ADD COLUMN `email_sent` TIMESTAMP NULL DEFAULT NULL ;

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailSubject_11','Reservation','Subject(status: payment received)','','text',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','Esam saņēmuši Jūsu maksājumu',0);

INSERT INTO ad_sitedata (name,tab,block,title,type,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailBody_11','Reservation','Email body(Status: payment received)','','textarea',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata_values (fid,lang,value,country) VALUES
    ((SELECT MAX(id) FROM ad_sitedata),'lv','<p>   Šis e-pasts ir izveidots automātiski, lūdzam uz to neatbildēt.</p>

<p>Vēlamies informēt, ka esam saņēmuši Jūsu maksājumu!.</p>
<p>
Pieraksta laiks: {start_time}</p>
<p>
Speciālists: {doctor_name}</p>
<p>
Iestāde: {clinic_name}</p>
{clinic_address}
{clinic_phone}
{clinic_email}
<p>
Pakalpojums: {service_name}</p>
<p>
Pieraksta statuss: {status}</p>
<hr />
<p>
{message}</p>',0);


CREATE TABLE `payment_callback_notifications` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`payment_reference` VARCHAR(255) NULL DEFAULT NULL,
`event_name` VARCHAR(255) NULL DEFAULT NULL,
`request_ip` VARCHAR(255) NULL DEFAULT NULL,
`request` JSON NULL DEFAULT NULL,
`created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
);

ALTER TABLE `mod_reservations` ADD COLUMN `vroom_create_required` TINYINT(1) NULL DEFAULT NULL;
