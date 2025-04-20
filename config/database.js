const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');

const dbPath = path.join(__dirname, '../data/sncf_vetements.db');
const db = new sqlite3.Database(dbPath);

function init() {
  const schema = fs.readFileSync(path.join(__dirname, '../database.sql'), 'utf8');
  
  return new Promise((resolve, reject) => {
    db.serialize(() => {
      db.run('PRAGMA foreign_keys = ON');
      
      schema.split(';').forEach(statement => {
        const sql = statement.trim();
        if (sql) {
          db.run(sql, err => {
            if (err) reject(err);
          });
        }
      });
      
      resolve();
    });
  });
}

module.exports = {
  db,
  init
};