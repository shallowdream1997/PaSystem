/**
 * Toast通知组件
 * 用于在页面右上角显示提示信息
 */

// Toast通知混入（Mixin）
const ToastMixin = {
    data() {
        return {
            toasts: [],
            toastIdCounter: 0
        }
    },
    
    methods: {
        /**
         * 显示Toast通知
         * @param {string} message - 消息内容
         * @param {string} type - 类型：success/error/info/warning
         * @param {number} duration - 显示时长（毫秒）
         */
        showToast(message, type = 'info', duration = 3000) {
            const id = ++this.toastIdCounter;
            const toast = {
                id,
                message,
                type,
                closing: false
            };
            this.toasts.push(toast);
            
            // 自动关闭
            if (duration > 0) {
                setTimeout(() => {
                    this.removeToast(id);
                }, duration);
            }
        },
        
        /**
         * 移除Toast
         * @param {number} id - Toast ID
         */
        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                this.toasts[index].closing = true;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300); // 等待动画完成
            }
        },
        
        /**
         * 显示成功消息
         * @param {string} message - 消息内容
         */
        showSuccess(message) {
            this.showToast(message, 'success', 3000);
        },
        
        /**
         * 显示错误消息
         * @param {string} message - 消息内容
         */
        showError(message) {
            this.showToast(message, 'error', 5000);
        },
        
        /**
         * 显示信息消息
         * @param {string} message - 消息内容
         */
        showInfo(message) {
            this.showToast(message, 'info', 3000);
        },
        
        /**
         * 显示警告消息
         * @param {string} message - 消息内容
         */
        showWarning(message) {
            this.showToast(message, 'warning', 4000);
        }
    }
};

// 导出混入
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastMixin;
}
