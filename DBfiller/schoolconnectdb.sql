-- Create the database
CREATE DATABASE SchoolConnectDB;
USE SchoolConnectDB;

-- Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    college_email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- UserSocialLinks table
CREATE TABLE UserSocialLinks (
    link_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform_name VARCHAR(50) NOT NULL,
    link_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- UserFriends table
CREATE TABLE UserFriends (
    connection_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id_sender INT NOT NULL,
    user_id_receiver INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id_sender) REFERENCES Users(user_id),
    FOREIGN KEY (user_id_receiver) REFERENCES Users(user_id)
);

-- Interests table
CREATE TABLE Interests (
    interest_id INT AUTO_INCREMENT PRIMARY KEY,
    interest_name VARCHAR(100) NOT NULL UNIQUE
);

-- UserInterests table
CREATE TABLE UserInterests (
    user_id INT NOT NULL,
    interest_id INT NOT NULL,
    PRIMARY KEY (user_id, interest_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (interest_id) REFERENCES Interests(interest_id)
);

-- Languages table
CREATE TABLE Languages (
    language_id INT AUTO_INCREMENT PRIMARY KEY,
    language_name VARCHAR(50) NOT NULL UNIQUE
);

-- UserLanguages table
CREATE TABLE UserLanguages (
    user_id INT NOT NULL,
    language_id INT NOT NULL,
    PRIMARY KEY (user_id, language_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (language_id) REFERENCES Languages(language_id)
);

-- UserUserGroups table
CREATE TABLE UserGroups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(200),
    creator_id INT NOT NULL,
    is_private BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES Users(user_id)
);

-- GroupMembers table
CREATE TABLE GroupMembers (
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    member_role VARCHAR(20) NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES UserGroups(group_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- Posts table
CREATE TABLE Posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_text TEXT NOT NULL,
    media_url VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    group_id INT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (group_id) REFERENCES UserGroups(group_id)
);

-- UserComments table
CREATE TABLE UserComments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Posts(post_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- DirectMessages table
CREATE TABLE DirectMessages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message_text TEXT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id),
    FOREIGN KEY (recipient_id) REFERENCES Users(user_id)
);