import { Editor } from '@tiptap/vue-3';

// const shortcutKeyMap = {
//   mod: isMacOS() ? { symbol: '⌘', readable: 'Command' } : { symbol: 'Ctrl', readable: 'Control' },
//   alt: isMacOS() ? { symbol: '⌥', readable: 'Option' } : { symbol: 'Alt', readable: 'Alt' },
//   shift: { symbol: '⇧', readable: 'Shift' },
// };

// const isClient = () => typeof window !== 'undefined';
// const isServer = () => !isClient();
// const isMacOS = () => isClient() && window.navigator.platform === 'MacIntel';

// const getShortcutKey = (key) => shortcutKeyMap[key.toLowerCase()] || { symbol: key, readable: key };
// const getShortcutKeys = (keys) => keys.map(getShortcutKey);

const getOutput = (editor, format) => {
  switch (format) {
    case 'json':
      return editor.getJSON();
    case 'html':
      return editor.getText() ? editor.getHTML() : '';
    default:
      return editor.getText();
  }
};

const isUrl = (text, options = { requireHostname: false }) => {
  if (text.includes('\n')) return false;

  try {
    const url = new URL(text);
    const blockedProtocols = ['javascript:', 'file:', 'vbscript:', ...(options.allowBase64 ? [] : ['data:'])];

    if (blockedProtocols.includes(url.protocol)) return false;
    if (options.allowBase64 && url.protocol === 'data:') return /^data:image\/[a-z]+;base64,/.test(text);
    if (url.hostname) return true;

    return (
      url.protocol !== '' &&
      (url.pathname.startsWith('//') || url.pathname.startsWith('http')) &&
      !options.requireHostname
    );
  } catch {
    return false;
  }
};

const sanitizeUrl = (url, options = {}) => {
  if (!url) return undefined;

  if (options.allowBase64 && url.startsWith('data:image')) {
    return isUrl(url, { requireHostname: false, allowBase64: true }) ? url : undefined;
  }

  return isUrl(url, { requireHostname: false, allowBase64: options.allowBase64 }) ||
    /^(\/|#|mailto:|sms:|fax:|tel:)/.test(url)
    ? url
    : `https://${url}`;
};

const blobUrlToBase64 = async (blobUrl) => {
  const response = await fetch(blobUrl);
  const blob = await response.blob();

  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      if (typeof reader.result === 'string') {
        resolve(reader.result);
      } else {
        reject(new Error('Failed to convert Blob to base64'));
      }
    };
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
};

const downloadImage = (url, filename) => {
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
};

const copyImage = (url) => {
  const image = document.createElement('img');
  image.src = url;
  image.style.position = 'fixed';
  image.style.top = '-10000px';
  document.body.appendChild(image);
  image.click();
  document.body.removeChild(image);
};

const copyLink = (url) => {
  const link = document.createElement('a');
  link.href = url;
  link.style.position = 'fixed';
  link.style.top = '-10000px';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const randomId = () => Math.random().toString(36).slice(2, 11);

const fileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      if (typeof reader.result === 'string') {
        resolve(reader.result);
      } else {
        reject(new Error('Failed to convert File to base64'));
      }
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
};

const validateFileOrBase64 = (input, options, originalFile, validFiles, errors) => {
  const { isValidType, isValidSize } = checkTypeAndSize(input, options);

  if (isValidType && isValidSize) {
    validFiles.push(originalFile);
  } else {
    if (!isValidType) errors.push({ file: input, reason: 'type' });
    if (!isValidSize) errors.push({ file: input, reason: 'size' });
  }
};

const checkTypeAndSize = (input, { allowedMimeTypes, maxFileSize }) => {
  const mimeType = input instanceof File ? input.type : base64MimeType(input);
  const size = input instanceof File ? input.size : atob(input.split(',')[1]).length;

  const isValidType =
    allowedMimeTypes.length === 0 ||
    allowedMimeTypes.includes(mimeType) ||
    allowedMimeTypes.includes(`${mimeType.split('/')[0]}/*`);

  const isValidSize = !maxFileSize || size <= maxFileSize;

  return { isValidType, isValidSize };
};

const base64MimeType = (encoded) => {
  const result = encoded.match(/data:([a-zA-Z0-9]+\/[a-zA-Z0-9-.+]+).*,.*/);
  return result && result.length > 1 ? result[1] : 'unknown';
};

const isBase64 = (str) => {
  if (str.startsWith('data:')) {
    const matches = str.match(/^data:[^;]+;base64,(.+)$/);
    if (matches && matches[1]) {
      str = matches[1];
    } else {
      return false;
    }
  }

  try {
    return btoa(atob(str)) === str;
  } catch {
    return false;
  }
};

const filterFiles = (files, options) => {
  const validFiles = [];
  const errors = [];

  files.forEach((file) => {
    const actualFile = 'src' in file ? file.src : file;

    if (actualFile instanceof File) {
      validateFileOrBase64(actualFile, options, file, validFiles, errors);
    } else if (typeof actualFile === 'string') {
      if (isBase64(actualFile)) {
        if (options.allowBase64) {
          validateFileOrBase64(actualFile, options, file, validFiles, errors);
        } else {
          errors.push({ file: actualFile, reason: 'base64NotAllowed' });
        }
      } else {
        if (!sanitizeUrl(actualFile, { allowBase64: options.allowBase64 })) {
          errors.push({ file: actualFile, reason: 'invalidBase64' });
        } else {
          validFiles.push(file);
        }
      }
    }
  });

  return [validFiles, errors];
};

export {
    filterFiles,
    // isClient,
    // isServer,
    // isMacOS,
    // getShortcutKey,
    // getShortcutKeys,
    getOutput,
    isUrl,
    sanitizeUrl,
    blobUrlToBase64,
    randomId,
    fileToBase64,
    checkTypeAndSize,
    base64MimeType,
    downloadImage,
    copyImage,
    copyLink,
    isBase64,
  };
