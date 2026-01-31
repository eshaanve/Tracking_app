CREATE DATABASE IF NOT EXISTS bus_tracker;
USE bus_tracker;

CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    driver_name VARCHAR(100),
    vehicle_number VARCHAR(50),
    route_id INT DEFAULT NULL,
    lat DOUBLE DEFAULT 0,
    lng DOUBLE DEFAULT 0,
    speed DOUBLE DEFAULT 0,
    last_gps_time DATETIME DEFAULT NULL,
    status ENUM('ONLINE','OFFLINE') DEFAULT 'OFFLINE'
);

CREATE TABLE IF NOT EXISTS routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    route_name VARCHAR(100) NOT NULL

);

CREATE TABLE IF NOT EXISTS bus_location (
    bus_id INT PRIMARY KEY,
    route_id INT NOT NULL,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    speed DOUBLE NOT NULL,
    last_updated DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS passengers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    age INT NOT NULL,
    `condition` VARCHAR(50) DEFAULT 'Normal'
);