function requireAuth(req, res, next) {
  if (!req.session.userId) {
    return res.redirect('/auth/login');
  }
  next();
}

function requireRole(roles) {
  return (req, res, next) => {
    if (!req.session.userId) {
      return res.redirect('/auth/login');
    }
    if (!roles.includes(req.session.role)) {
      return res.redirect('/');
    }
    next();
  };
}

function injectUser(req, res, next) {
  if (req.session.userId) {
    req.user = {
      id: req.session.userId,
      role: req.session.role
    };
    res.locals.user = req.user;
  }
  next();
}

module.exports = {
  requireAuth,
  requireRole,
  injectUser
};