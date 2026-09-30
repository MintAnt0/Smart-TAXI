// server.js
// Ce fichier gère le serveur HTTP et la connexion à la base de données MySQL
const mysql = require('mysql');

const connection = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '', // Mets ton mot de passe MySQL
  database: 'taxi_reservation'
});

connection.connect((err) => {
  if (err) {
    console.error('Erreur de connexion à la base de données :', err);
    return;
  }
  console.log('Connecté à la base de données MySQL');
});

module.exports = connection;

const http = require('http');
const fs = require('fs');
const path = require('path');
const db = require('./db');
const { parse } = require('querystring');

const server = http.createServer((req, res) => {
  if (req.method === 'GET') {
    // Sert le HTML
    if (req.url === '/') {
      fs.readFile(path.join(__dirname, 'public', 'index.html'), (err, content) => {
        if (err) {
          res.writeHead(500);
          res.end('Erreur serveur');
          return;
        }
        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(content);
      });
    }
  }

  if (req.method === 'POST' && req.url === '/submit-reservation') {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString(); // Transformer le buffer en string
    });

    req.on('end', () => {
      const data = parse(body);

      const sql = `
        INSERT INTO reservations 
        (nom, prenom, telephone, email, adresse_depart, adresse_arrivee, date_reservation, heure_reservation, options)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      `;

      const values = [
        data.nom, data.prenom, data.telephone, data.email,
        data.adresse_depart, data.adresse_arrivee,
        data.date_reservation, data.heure_reservation,
        data.options || ''
      ];

      db.query(sql, values, (err, result) => {
        if (err) {
          console.error('Erreur MySQL :', err);
          res.writeHead(500);
          res.end('Erreur serveur');
          return;
        }

        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end('<h2>Réservation reçue avec succès !</h2><a href="/">Retour</a>');
      });
    });
  }
});

server.listen(3000, () => {
  console.log('Serveur en écoute sur http://localhost:3000');
});
