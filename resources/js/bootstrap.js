/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const nativeFetch = window.fetch.bind(window);

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function normalizeFetchUrl(input) {
  if (typeof input === 'string') {
    return new URL(input, window.location.origin);
  }

  if (input instanceof Request) {
    return new URL(input.url, window.location.origin);
  }

  return new URL(String(input), window.location.origin);
}

function shouldForceJsonHeaders(url) {
  if (url.origin !== window.location.origin) {
    return false;
  }

  return true;
}

window.fetch = (input, init = {}) => {
  const url = normalizeFetchUrl(input);
  const headers = new Headers(
    init.headers || (input instanceof Request ? input.headers : undefined) || {}
  );
  const method = String(init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();

  if (shouldForceJsonHeaders(url)) {
    if (!headers.has('Accept')) {
      headers.set('Accept', 'application/json');
    }

    if (!headers.has('X-Requested-With')) {
      headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && !headers.has('X-CSRF-TOKEN')) {
      const token = csrfToken();

      if (token) {
        headers.set('X-CSRF-TOKEN', token);
      }
    }
  }

  return nativeFetch(input, {
    ...init,
    credentials: init.credentials ?? 'same-origin',
    headers,
  });
};


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */
