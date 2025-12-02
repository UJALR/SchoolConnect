# School Connect Website App

## Final Project for CST8268 - Project

**Project Name:** School Connect

**Instructor:** Mervat Mustafa

## Description

School Connect is a lightweight social platform for students to create groups, share posts, and message other users. The project is built with PHP (server-rendered pages + APIs) for the core app and a small Node.js Socket.IO server to provide realtime chat. Messages persist in a MySQL database so conversations are available across sessions.

**Group Members:**
- Jordan Tsague Lekpa
- Shady Boles
- Gamaliel Cabana
- Ujjawal Rai

## Getting Started

Prerequisites:
- XAMPP (Apache + MySQL) or another PHP + MySQL stack
- Node.js (v16+ recommended) and npm

Installation & run (local development):

1. Place the project folder inside XAMPP's `htdocs` directory (e.g. `C:\xampp\htdocs\SchoolConnect`). The web server must serve the project's main directory from `htdocs`.
2. Start Apache and MySQL using the XAMPP control panel.
3. Import the database schema located in `DBfiller/schoolconnectdb.sql` into your MySQL server (use phpMyAdmin or mysql CLI).
4. Install Node dependencies for the chat server:

```powershell
cd C:\xampp\htdocs\SchoolConnect\server
npm install
```

5. Start the Node chat server (runs alongside XAMPP and provides realtime messaging):

```powershell
cd C:\xampp\htdocs\SchoolConnect\server
npm start
```

6. Open the app in your browser at `http://localhost/SchoolConnect/` (or the path matching the folder name you used inside `htdocs`). Log in or create users and test features.

Notes:
- The PHP application (served by XAMPP) handles pages, APIs, and database access.
- The Node server (Socket.IO) handles realtime message delivery; it should be running while testing chat for realtime behavior. The chat server will persist messages into the same MySQL database so conversations remain after page reloads.

## Acknowledgements

- Chat functionality and related design ideas were primarily implemented with assistance from Gemini.

