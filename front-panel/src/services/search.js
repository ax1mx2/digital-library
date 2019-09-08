'use strict';

if (!window.fetch) {
  window.fetch = require('whatwg-fetch');
}
if (!window.Promise) {
  window.Promise = require('promise-polyfill');
}

export function fetchBooks(category, title, authors, page = 1) {
  return fetch(window.dl_data.fetchBooksUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({category, title, authors, page}),
  }).then((res) => {
    return res.json();
  });
}
