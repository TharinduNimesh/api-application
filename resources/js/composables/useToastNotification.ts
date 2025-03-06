import { useToast } from 'primevue/usetoast';

export function useToastNotification() {
  const toast = useToast();

  const showSuccess = (message: string) => {
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: message,
      life: 3000
    });
  };

  const showError = (error: any) => {
    const message = error.response?.data?.message || 'An unexpected error occurred';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: message,
      life: 3000
    });
  };

  const showWarning = (message: string) => {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: message,
      life: 3000
    });
  };

  const showInfo = (message: string) => {
    toast.add({
      severity: 'info',
      summary: 'Information',
      detail: message,
      life: 3000
    });
  };

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo
  };
}