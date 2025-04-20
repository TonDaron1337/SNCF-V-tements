import express from 'express';
import compression from 'compression';
import helmet from 'helmet';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import serveStatic from 'serve-static';

const __dirname = dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 3000;

// Middleware de sécurité
app.use(helmet({
  contentSecurityPolicy: false,
  crossOriginEmbedderPolicy: false
}));

// Compression GZIP
app.use(compression());

// Servir les fichiers statiques
app.use(serveStatic(join(__dirname, 'public')));

// Redirection des URLs
app.use((req, res, next) => {
  // Retirer /hugo/sncf-vetements de l'URL si présent
  if (req.url.startsWith('/hugo/sncf-vetements')) {
    const newUrl = req.url.replace('/hugo/sncf-vetements', '');
    return res.redirect(301, newUrl);
  }
  next();
});

// Gestion des routes PHP
app.get('/:page', (req, res, next) => {
  const page = req.params.page;
  if (!page.endsWith('.php')) {
    req.url = `/${page}.php`;
  }
  next();
});

// Pages d'erreur personnalisées
app.use((req, res, next) => {
  res.status(404).sendFile(join(__dirname, '404.php'));
});

app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).sendFile(join(__dirname, '500.php'));
});

// Démarrage du serveur
app.listen(PORT, () => {
  console.log(`Serveur démarré sur le port ${PORT}`);
});