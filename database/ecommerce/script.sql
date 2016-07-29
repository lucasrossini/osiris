SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- -----------------------------------------------------
-- Table `ecom_product`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(200) NOT NULL ,
  `slug` VARCHAR(200) NOT NULL ,
  `description` TEXT NOT NULL ,
  `visible` TINYINT(1)  NOT NULL ,
  `price` FLOAT NOT NULL ,
  `promotional_price` FLOAT NULL ,
  `sku` VARCHAR(30) NULL ,
  `image` VARCHAR(150) NULL ,
  `stock` INT NULL ,
  `order_limit` INT NOT NULL ,
  `weight` FLOAT NOT NULL ,
  `width` FLOAT NOT NULL ,
  `height` FLOAT NOT NULL ,
  `length` FLOAT NOT NULL ,
  `free_shipping` TINYINT(1)  NOT NULL ,
  `date` DATE NOT NULL ,
  `time` TIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_category`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_category` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `slug` VARCHAR(100) NOT NULL ,
  `visible` TINYINT(1)  NOT NULL ,
  `parent_id` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_category_parent_id` (`parent_id` ASC) ,
  CONSTRAINT `fk_ecom_category_parent_id`
    FOREIGN KEY (`parent_id` )
    REFERENCES `ecom_category` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_product_category`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product_category` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `product_id` INT NOT NULL ,
  `category_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_product_category_product_id` (`product_id` ASC) ,
  INDEX `fk_ecom_product_category_category_id` (`category_id` ASC) ,
  CONSTRAINT `fk_ecom_product_category_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_product_category_category_id`
    FOREIGN KEY (`category_id` )
    REFERENCES `ecom_category` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_tag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_tag` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tag` VARCHAR(50) NOT NULL ,
  `slug` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_product_tag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product_tag` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `product_id` INT NOT NULL ,
  `tag_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_product_tag_product_id` (`product_id` ASC) ,
  INDEX `fk_ecom_product_tag_tag_id` (`tag_id` ASC) ,
  CONSTRAINT `fk_ecom_product_tag_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_product_tag_tag_id`
    FOREIGN KEY (`tag_id` )
    REFERENCES `ecom_tag` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_client`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_client` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `email` VARCHAR(150) NOT NULL ,
  `password` VARCHAR(255) NOT NULL ,
  `cpf` VARCHAR(14) NOT NULL ,
  `signup_date` DATE NOT NULL ,
  `signup_time` TIME NOT NULL ,
  `phone` VARCHAR(13) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_address`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_address` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `client_id` INT NOT NULL ,
  `title` VARCHAR(100) NOT NULL ,
  `addressee` VARCHAR(100) NOT NULL ,
  `street` VARCHAR(150) NOT NULL ,
  `number` VARCHAR(50) NOT NULL ,
  `complement` VARCHAR(80) NULL ,
  `neighborhood` VARCHAR(100) NOT NULL ,
  `zip_code` VARCHAR(9) NOT NULL ,
  `state_id` INT NOT NULL ,
  `city_id` INT NOT NULL ,
  `default` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_address_client_id` (`client_id` ASC) ,
  CONSTRAINT `fk_ecom_address_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `ecom_client` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_payment_method`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_payment_method` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `active` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_shipping_method`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_shipping_method` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `price` FLOAT NULL ,
  `unit` TINYINT(1) NOT NULL ,
  `active` TINYINT(1)  NOT NULL ,
  `correios_code` VARCHAR(20) NULL ,
  `delivery_days` INT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_order`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_order` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `client_id` INT NOT NULL ,
  `address_id` INT NOT NULL ,
  `payment_method_id` INT NOT NULL ,
  `shipping_method_id` INT NOT NULL ,
  `shipping_price` FLOAT NOT NULL ,
  `total` FLOAT NOT NULL ,
  `delivery_days` INT NOT NULL ,
  `date` DATE NOT NULL ,
  `time` TIME NOT NULL ,
  `status` TINYINT(1) NOT NULL ,
  `gift` TINYINT(1)  NOT NULL ,
  `tracking_code` VARCHAR(40) NULL ,
  `payment_datetime` DATETIME NULL ,
  `dispatch_datetime` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_order_address_id` (`address_id` ASC) ,
  INDEX `fk_ecom_order_payment_method_id` (`payment_method_id` ASC) ,
  INDEX `fk_ecom_order_shipping_method_id` (`shipping_method_id` ASC) ,
  INDEX `fk_ecom_order_client_id` (`client_id` ASC) ,
  CONSTRAINT `fk_ecom_order_address_id`
    FOREIGN KEY (`address_id` )
    REFERENCES `ecom_address` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_order_payment_method_id`
    FOREIGN KEY (`payment_method_id` )
    REFERENCES `ecom_payment_method` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_order_shipping_method_id`
    FOREIGN KEY (`shipping_method_id` )
    REFERENCES `ecom_shipping_method` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_order_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `ecom_client` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_variation_type`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_variation_type` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(50) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_product_variation`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product_variation` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `product_id` INT NOT NULL ,
  `variation_type_id` INT NOT NULL ,
  `variation` VARCHAR(100) NOT NULL ,
  `variation_stock` INT NOT NULL ,
  `variation_sku` VARCHAR(30) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_product_variation_product_id` (`product_id` ASC) ,
  INDEX `fk_ecom_product_variation_variation_type_id` (`variation_type_id` ASC) ,
  CONSTRAINT `fk_ecom_product_variation_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_product_variation_variation_type_id`
    FOREIGN KEY (`variation_type_id` )
    REFERENCES `ecom_variation_type` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_order_product`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_order_product` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `order_id` INT NOT NULL ,
  `product_id` INT NOT NULL ,
  `variation_id` INT NULL ,
  `quantity` INT NOT NULL ,
  `price` FLOAT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_order_product_order_id` (`order_id` ASC) ,
  INDEX `fk_ecom_order_product_product_id` (`product_id` ASC) ,
  INDEX `fk_ecom_order_product_variation_id` (`variation_id` ASC) ,
  CONSTRAINT `fk_ecom_order_product_order_id`
    FOREIGN KEY (`order_id` )
    REFERENCES `ecom_order` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_order_product_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_order_product_variation_id`
    FOREIGN KEY (`variation_id` )
    REFERENCES `ecom_product_variation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_wishlist_product`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_wishlist_product` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `client_id` INT NOT NULL ,
  `product_id` INT NOT NULL ,
  `date` DATE NOT NULL ,
  `time` TIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_wishlist_product_product_id` (`product_id` ASC) ,
  INDEX `fk_ecom_wishlist_product_client_id` (`client_id` ASC) ,
  CONSTRAINT `fk_ecom_wishlist_product_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_wishlist_product_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `ecom_client` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_product_photo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product_photo` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `product_id` INT NOT NULL ,
  `file` VARCHAR(100) NOT NULL ,
  `subtitle` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_product_photo_product_id` (`product_id` ASC) ,
  CONSTRAINT `fk_ecom_product_photo_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_search`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_search` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `query` VARCHAR(100) NOT NULL ,
  `date` DATE NOT NULL ,
  `time` TIME NOT NULL ,
  `client_id` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_search_client_id` (`client_id` ASC) ,
  CONSTRAINT `fk_ecom_search_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `ecom_client` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_product_view`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_product_view` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `client_id` INT NOT NULL ,
  `product_id` INT NOT NULL ,
  `date` DATE NOT NULL ,
  `time` TIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_ecom_product_view_client_id` (`client_id` ASC) ,
  INDEX `fk_ecom_product_view_product_id` (`product_id` ASC) ,
  CONSTRAINT `fk_ecom_product_view_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `ecom_client` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ecom_product_view_product_id`
    FOREIGN KEY (`product_id` )
    REFERENCES `ecom_product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ecom_settings`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ecom_settings` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `zip_code` VARCHAR(9) NOT NULL ,
  `gift_price` FLOAT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `ecom_payment_method`
-- -----------------------------------------------------
INSERT INTO `ecom_payment_method` (`id`, `name`, `active`) VALUES (1, 'PagSeguro', 1);

-- -----------------------------------------------------
-- Data for table `ecom_shipping_method`
-- -----------------------------------------------------
INSERT INTO `ecom_shipping_method` (`id`, `name`, `price`, `unit`, `active`, `correios_code`, `delivery_days`) VALUES (1, 'PAC', NULL, 2, 1, '41106', 0);
INSERT INTO `ecom_shipping_method` (`id`, `name`, `price`, `unit`, `active`, `correios_code`, `delivery_days`) VALUES (2, 'SEDEX', NULL, 2, 1, '40010', 0);
INSERT INTO `ecom_shipping_method` (`id`, `name`, `price`, `unit`, `active`, `correios_code`, `delivery_days`) VALUES (3, 'SEDEX 10', NULL, 2, 1, '40215', 0);
INSERT INTO `ecom_shipping_method` (`id`, `name`, `price`, `unit`, `active`, `correios_code`, `delivery_days`) VALUES (4, 'Frete grátis', 0, 2, 1, NULL, 5);

-- -----------------------------------------------------
-- Data for table `ecom_settings`
-- -----------------------------------------------------
INSERT INTO `ecom_settings` (`id`, `zip_code`, `gift_price`) VALUES (1, '00000-000', 0);
