INSERT INTO Users (college_email, password_hash, username, full_name, bio, profile_picture)
VALUES
('alice@algonquin.edu', 'hash123', 'aliceW', 'Alice Wong', 'Web dev enthusiast and coffee lover.', 'alice.jpg'),
('bob@algonquin.edu', 'hash456', 'bobM', 'Bob Martinez', 'Always tinkering with backend APIs.', 'bob.jpg'),
('carla@algonquin.edu', 'hash789', 'carlaD', 'Carla Dsouza', 'UX designer in training.', 'carla.jpg');

INSERT INTO UserSocialLinks (user_id, platform_name, link_url)
VALUES
(1, 'LinkedIn', 'https://linkedin.com/in/alicew'),
(2, 'GitHub', 'https://github.com/bobmartinez'),
(3, 'Instagram', 'https://instagram.com/carla.designs');

INSERT INTO UserFriends (user_id_sender, user_id_receiver, status)
VALUES
(1, 2, 'accepted'),
(2, 3, 'pending'),
(3, 1, 'accepted');

INSERT INTO Interests (interest_name)
VALUES
('Web Development'),
('UI/UX Design'),
('Cybersecurity'),
('Machine Learning');

INSERT INTO UserInterests (user_id, interest_id)
VALUES
(1, 1),
(2, 4),
(3, 2);

INSERT INTO Languages (language_name)
VALUES
('English'),
('French'),
('Spanish');

INSERT INTO UserLanguages (user_id, language_id)
VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 1),
(3, 3);

INSERT INTO UserGroups (group_name, description, creator_id, is_private)
VALUES
('Dev Circle', 'Group for web development discussions.', 1, 0),
('Design Hub', 'UI/UX design inspiration and critique.', 3, 1);

INSERT INTO GroupMembers (group_id, user_id, member_role)
VALUES
(1, 1, 'admin'),
(1, 2, 'member'),
(2, 3, 'admin');

INSERT INTO Posts (user_id, post_text, media_url, group_id)
VALUES
(1, 'Just finished my first React app!', 'react_app.png', 1),
(3, 'Check out this new color palette for mobile UI.', 'palette.jpg', 2);

INSERT INTO UserComments (post_id, user_id, comment_text)
VALUES
(1, 2, 'Nice work! React is awesome.'),
(2, 1, 'Love the contrast. Very clean!');

INSERT INTO DirectMessages (sender_id, recipient_id, message_text)
VALUES
(1, 2, 'Hey Bob, want to pair on the API project?'),
(2, 1, 'Sure! Let’s sync up tomorrow.'),
(3, 1, 'Can you review my portfolio layout?');