import { toast } from "vue3-toastify";

export function useNotify() {
  return {
    sucesso: (msg, opts = {}) => toast.success(msg, { autoClose: 4000, ...opts }),
    erro: (msg, opts = {}) => toast.error(msg, { autoClose: 7000, role: "alert", ...opts }),
    info: (msg, opts = {}) => toast.info(msg, { autoClose: 4000, ...opts }),
  };
}
