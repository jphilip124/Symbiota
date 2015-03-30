ALTER TABLE `uploadtaxa` 
  ADD COLUMN `uploadStatus` VARCHAR(45) NULL AFTER `Hybrid`,
  DROP COLUMN `KingdomID`;

ALTER TABLE `uploadtaxa` 
  ADD INDEX `parentStr_index` (`ParentStr` ASC),
  ADD INDEX `acceptedStr_index` (`AcceptedStr` ASC),
  ADD INDEX `unitname1_index` (`UnitName1` ASC),
  ADD INDEX `sourceParentId_index` (`SourceParentId` ASC),
  ADD INDEX `acceptance_index` (`Acceptance` ASC);


ALTER TABLE `taxa` 
  DROP COLUMN `KingdomID`,
  DROP COLUMN `kingdomName`;


ALTER TABLE `omoccurrences` 
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`;


DROP TABLE `userpermissions`;


# Event date range within omoccurrence table


#Need to add condition to run only if collid exists
ALTER TABLE `omoccurrencesfulltext` 
  DROP COLUMN `collid`,
  DROP INDEX `Index_occurfull_collid` ;


# Add one to many relationship between collections and institutions
# Add one to many relationship between collection to agent



#Create an occurrence type table



#Add one to many relationship between collections and institutions



#Add one to many relationship between collection to agent



#Review pubprofile (adminpublications)


#Collection GUID issue




SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE `omoccurpoints`;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO omoccurpoints (occid,point)
SELECT occid,Point(decimalLatitude, decimalLongitude) FROM omoccurrences WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;

DELIMITER //
DROP TRIGGER IF EXISTS `omoccurpoints_insert`//
CREATE TRIGGER `omoccurpoints_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
  IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
	  INSERT INTO omoccurpoints (
		`occid`,
		`point`
	  ) VALUES (
		NEW.`occid`,
		Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`)
	  );
  END IF;
END
//

DROP TRIGGER IF EXISTS `omoccurpoints_update`//
CREATE TRIGGER `omoccurpoints_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
  IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
	  UPDATE omoccurpoints SET
		`point` = Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`)
	  WHERE `occid` = NEW.`occid`;
  END IF;
END
//

DROP TRIGGER IF EXISTS `omoccurpoints_delete`//
CREATE TRIGGER `omoccurpoints_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
  DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
END
//

DELIMITER ;



