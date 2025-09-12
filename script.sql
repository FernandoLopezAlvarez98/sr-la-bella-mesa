-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `labellamesa` DEFAULT CHARACTER SET utf8 COLLATE utf8_spanish2_ci ;
USE `labellamesa` ;

-- -----------------------------------------------------
-- Table `labellamesa`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`usuario` (
  `id_usuario` VARCHAR(36) NOT NULL,
  `nombre` VARCHAR(45) NOT NULL,
  `correo` VARCHAR(45) NULL,
  `telefono` VARCHAR(10) NOT NULL,
  `password_hash` VARCHAR(256) NOT NULL,
  `rol` VARCHAR(45) NOT NULL,
  `fecha_registro` DATE NOT NULL,
  `foto_perfil` VARCHAR(1000) NULL,
  `ultimo_acceso` TIMESTAMP DEFAULT  CURRENT_TIMESTAMP ON UPDATE  CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `id_usuario_UNIQUE` (`id_usuario` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `labellamesa`.`restaurante`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`restaurante` (
  `id_restaurante` VARCHAR(36) NOT NULL,
  `id_usuario` VARCHAR(36) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `direccion` VARCHAR(300) NOT NULL,
  `telefono` VARCHAR(10) NOT NULL,
  `tipo_cocina` VARCHAR(100) NULL,
  `horario_apertura` TIME NOT NULL,
  `horario_cierre` TIME NOT NULL,
  `capacidad_total` INT NOT NULL,
  `foto_portada` VARCHAR(1000) NOT NULL,
  `fecha_registro` TIMESTAMP DEFAULT  CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id_restaurante`),
  INDEX `fk_id_usuario_idx` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `fk_usuario_restaurante`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `labellamesa`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `labellamesa`.`mesa`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`mesa` (
  `id_mesa` VARCHAR(36) NOT NULL,
  `id_restaurante` VARCHAR(36) NOT NULL,
  `numero` INT NOT NULL,
  `capacidad` INT NOT NULL,
  PRIMARY KEY (`id_mesa`),
  INDEX `fk_id_restaurante_idx` (`id_restaurante` ASC) VISIBLE,
  CONSTRAINT `fk_id_restaurante`
    FOREIGN KEY (`id_restaurante`)
    REFERENCES `labellamesa`.`restaurante` (`id_restaurante`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `labellamesa`.`reservacion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`reservacion` (
  `id_reservacion` VARCHAR(36) NOT NULL,
  `id_usario` VARCHAR(36) NOT NULL,
  `id_restaurante` VARCHAR(36) NOT NULL,
  `id_mesa` VARCHAR(36) NOT NULL,
  `fecha_reserva` DATE NOT NULL,
  `hora_reserva` TIME NOT NULL,
  `num_personas` INT NOT NULL,
  `estado` VARCHAR(45) NOT NULL,
  `fecha_creaciom` DATETIME NOT NULL,
  `fecha_actulizacion` TIMESTAMP DEFAULT  CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id_reservacion`),
  INDEX `fk_id_usuario_reservacion_idx` (`id_usario` ASC) VISIBLE,
  INDEX `fk_id_restaurante_reservacion_idx` (`id_restaurante` ASC) VISIBLE,
  INDEX `fk_id_mesa_reservacion_idx` (`id_mesa` ASC) VISIBLE,
  CONSTRAINT `fk_id_usuario_reservacion`
    FOREIGN KEY (`id_usario`)
    REFERENCES `labellamesa`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_id_restaurante_reservacion`
    FOREIGN KEY (`id_restaurante`)
    REFERENCES `labellamesa`.`restaurante` (`id_restaurante`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_id_mesa_reservacion`
    FOREIGN KEY (`id_mesa`)
    REFERENCES `labellamesa`.`mesa` (`id_mesa`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `labellamesa`.`resena`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`resena` (
  `id_resena` VARCHAR(36) NOT NULL,
  `id_usuario` VARCHAR(36) NOT NULL,
  `id_restaurante` VARCHAR(36) NOT NULL,
  `calificacion` INT NOT NULL,
  `comentario` VARCHAR(200) NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id_resena`),
  INDEX `fk_usuario_resena_idx` (`id_usuario` ASC) VISIBLE,
  INDEX `fk_restaurante_resena_idx` (`id_restaurante` ASC) VISIBLE,
  CONSTRAINT `fk_usuario_resena`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `labellamesa`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_restaurante_resena`
    FOREIGN KEY (`id_restaurante`)
    REFERENCES `labellamesa`.`restaurante` (`id_restaurante`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `labellamesa`.`pago`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `labellamesa`.`pago` (
  `id_pago` VARCHAR(36) NOT NULL,
  `id_reservacion` VARCHAR(36) NOT NULL,
  `monto` FLOAT(10,2) NOT NULL,
  `metodo_pago` VARCHAR(45) NOT NULL,
  `estado` VARCHAR(45) NOT NULL,
  `fecha_pago` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY (`id_pago`),
  INDEX `fk_reservacion_pago_idx` (`id_reservacion` ASC) VISIBLE,
  CONSTRAINT `fk_reservacion_pago`
    FOREIGN KEY (`id_reservacion`)
    REFERENCES `labellamesa`.`reservacion` (`id_reservacion`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
