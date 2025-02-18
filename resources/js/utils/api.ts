export const getMethodSeverity = (method: string): string => {
  const severities: Record<string, string> = {
    GET: 'success',
    POST: 'info',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'warning'
  };
  return severities[method] || 'info';
};
