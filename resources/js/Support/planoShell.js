/**
 * The contract between the application shell and a Plano page header: the shell
 * offers the path it worked out for the page, and the header tells the shell that
 * the page draws its own canvas and what the page is called (`setTitle`), which
 * the shell puts on the browser tab unless the page sets a title of its own.
 */
export const planoShellKey = Symbol('plano-shell')
