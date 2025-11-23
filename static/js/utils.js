/**
 * 小绿茶XSS反连平台 - 工具函数库
 * 作者: AI Assistant
 * 版本: 1.0.0
 */

/**
 * 编码工具类
 */
const Encoder = {
    /**
     * URL编码
     */
    urlEncode: function(str) {
        return encodeURIComponent(str).replace(/[!'()*]/g, function(c) {
            return '%' + c.charCodeAt(0).toString(16).toUpperCase();
        });
    },
    
    /**
     * URL解码
     */
    urlDecode: function(str) {
        return decodeURIComponent(str);
    },
    
    /**
     * Base64编码
     */
    base64Encode: function(str) {
        try {
            return btoa(unescape(encodeURIComponent(str)));
        } catch (e) {
            console.error('Base64 encode error:', e);
            return '';
        }
    },
    
    /**
     * Base64解码
     */
    base64Decode: function(str) {
        try {
            return decodeURIComponent(escape(atob(str)));
        } catch (e) {
            console.error('Base64 decode error:', e);
            return '';
        }
    },
    
    /**
     * HTML编码
     */
    htmlEncode: function(str) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    },
    
    /**
     * HTML解码
     */
    htmlDecode: function(str) {
        const map = {
            '&amp;': '&',
            '&lt;': '<',
            '&gt;': '>',
            '&quot;': '"',
            '&#039;': "'"
        };
        return str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function(m) { return map[m]; });
    },
    
    /**
     * JavaScript字符串编码
     */
    jsEncode: function(str) {
        return str.replace(/[\\"'\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g, function(a) {
            return '\\u' + ('0000' + a.charCodeAt(0).toString(16)).substr(-4);
        });
    },
    
    /**
     * CSS编码
     */
    cssEncode: function(str) {
        return str.replace(/[^\w\-]/g, function(a) {
            return '\\' + a.charCodeAt(0).toString(16).toUpperCase() + ' ';
        });
    },
    
    /**
     * Unicode编码
     */
    unicodeEncode: function(str) {
        return str.replace(/[\s\S]/g, function(a) {
            return '\\u' + ('0000' + a.charCodeAt(0).toString(16)).substr(-4);
        });
    }
};

/**
 * 验证工具类
 */
const Validator = {
    /**
     * 验证IP地址
     */
    isIP: function(ip) {
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        return ipRegex.test(ip);
    },
    
    /**
     * 验证IPv6地址
     */
    isIPv6: function(ip) {
        const ipv6Regex = /^(([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/;
        return ipv6Regex.test(ip);
    },
    
    /**
     * 验证域名
     */
    isDomain: function(domain) {
        const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?(?:\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?)*$/;
        return domainRegex.test(domain);
    },
    
    /**
     * 验证邮箱
     */
    isEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    /**
     * 验证URL
     */
    isURL: function(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    },
    
    /**
     * 验证端口号
     */
    isPort: function(port) {
        const portNum = parseInt(port);
        return portNum >= 1 && portNum <= 65535;
    },
    
    /**
     * 验证文件名
     */
    isValidFilename: function(filename) {
        const invalidChars = /[<>:"|?*\/\\]/;
        return !invalidChars.test(filename) && filename.length > 0 && filename.length <= 255;
    }
};

/**
 * 网络工具类
 */
const Network = {
    /**
     * 解析User-Agent
     */
    parseUserAgent: function(ua) {
        const result = {
            browser: 'Unknown',
            version: 'Unknown',
            os: 'Unknown',
            device: 'Desktop'
        };
        
        if (!ua) return result;
        
        // 浏览器检测
        if (ua.includes('Chrome/') && !ua.includes('Edg/')) {
            result.browser = 'Chrome';
            result.version = ua.match(/Chrome\/(\d+)/)?.[1] || 'Unknown';
        } else if (ua.includes('Firefox/')) {
            result.browser = 'Firefox';
            result.version = ua.match(/Firefox\/(\d+)/)?.[1] || 'Unknown';
        } else if (ua.includes('Safari/') && !ua.includes('Chrome/')) {
            result.browser = 'Safari';
            result.version = ua.match(/Version\/(\d+)/)?.[1] || 'Unknown';
        } else if (ua.includes('Edg/')) {
            result.browser = 'Edge';
            result.version = ua.match(/Edg\/(\d+)/)?.[1] || 'Unknown';
        } else if (ua.includes('OPR/') || ua.includes('Opera/')) {
            result.browser = 'Opera';
            result.version = ua.match(/(?:OPR|Opera)\/(\d+)/)?.[1] || 'Unknown';
        }
        
        // 操作系统检测
        if (ua.includes('Windows')) {
            result.os = 'Windows';
        } else if (ua.includes('Mac OS X')) {
            result.os = 'macOS';
        } else if (ua.includes('Linux')) {
            result.os = 'Linux';
        } else if (ua.includes('Android')) {
            result.os = 'Android';
        } else if (ua.includes('iPhone') || ua.includes('iPad')) {
            result.os = 'iOS';
        }
        
        // 设备类型检测
        if (ua.includes('Mobile') || ua.includes('Android')) {
            result.device = 'Mobile';
        } else if (ua.includes('Tablet') || ua.includes('iPad')) {
            result.device = 'Tablet';
        }
        
        return result;
    },
    
    /**
     * 获取IP地址信息
     */
    getIPInfo: function(ip) {
        // 这里可以集成IP查询服务
        return {
            country: 'Unknown',
            region: 'Unknown',
            city: 'Unknown',
            isp: 'Unknown',
            ip: ip
        };
    },
    
    /**
     * 检测是否为内网IP
     */
    isPrivateIP: function(ip) {
        const privateRanges = [
            /^10\./,
            /^172\.(1[6-9]|2[0-9]|3[01])\./,
            /^192\.168\./,
            /^127\./,
            /^169\.254\./
        ];
        
        return privateRanges.some(range => range.test(ip));
    },
    
    /**
     * 检测是否为代理IP
     */
    isProxyIP: function(ip) {
        // 这里可以集成代理检测服务
        return false;
    }
};

/**
 * 字符串工具类
 */
const StringUtils = {
    /**
     * 截断字符串
     */
    truncate: function(str, length = 100, suffix = '...') {
        if (str.length <= length) return str;
        return str.substring(0, length - suffix.length) + suffix;
    },
    
    /**
     * 首字母大写
     */
    capitalize: function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },
    
    /**
     * 驼峰命名
     */
    camelCase: function(str) {
        return str.replace(/[-_\s]+(.)?/g, function(match, chr) {
            return chr ? chr.toUpperCase() : '';
        });
    },
    
    /**
     * 生成随机字符串
     */
    randomString: function(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },
    
    /**
     * 生成随机ID
     */
    randomId: function(prefix = 'id') {
        return prefix + '_' + Math.random().toString(36).substr(2, 9);
    },
    
    /**
     * 计算字符串哈希值
     */
    hashCode: function(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        return hash;
    },
    
    /**
     * 格式化JSON字符串
     */
    formatJSON: function(str) {
        try {
            return JSON.stringify(JSON.parse(str), null, 2);
        } catch (e) {
            return str;
        }
    },
    
    /**
     * 压缩JSON字符串
     */
    minifyJSON: function(str) {
        try {
            return JSON.stringify(JSON.parse(str));
        } catch (e) {
            return str;
        }
    }
};

/**
 * 数组工具类
 */
const ArrayUtils = {
    /**
     * 去重
     */
    unique: function(arr) {
        return [...new Set(arr)];
    },
    
    /**
     * 扁平化
     */
    flatten: function(arr) {
        return arr.reduce((flat, item) => 
            flat.concat(Array.isArray(item) ? this.flatten(item) : item), []
        );
    },
    
    /**
     * 分组
     */
    groupBy: function(arr, key) {
        return arr.reduce((groups, item) => {
            const group = item[key];
            groups[group] = groups[group] || [];
            groups[group].push(item);
            return groups;
        }, {});
    },
    
    /**
     * 排序
     */
    sortBy: function(arr, key, order = 'asc') {
        return arr.sort((a, b) => {
            const aVal = a[key];
            const bVal = b[key];
            
            if (order === 'asc') {
                return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
            } else {
                return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
            }
        });
    },
    
    /**
     * 搜索
     */
    search: function(arr, keyword, keys = []) {
        if (!keyword) return arr;
        
        const lowerKeyword = keyword.toLowerCase();
        return arr.filter(item => {
            if (keys.length > 0) {
                return keys.some(key => {
                    const value = item[key];
                    return value && value.toString().toLowerCase().includes(lowerKeyword);
                });
            } else {
                return Object.values(item).some(value => 
                    value && value.toString().toLowerCase().includes(lowerKeyword)
                );
            }
        });
    },
    
    /**
     * 分页
     */
    paginate: function(arr, page, pageSize) {
        const start = (page - 1) * pageSize;
        const end = start + pageSize;
        return arr.slice(start, end);
    }
};

/**
 * 数学工具类
 */
const MathUtils = {
    /**
     * 随机数
     */
    random: function(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },
    
    /**
     * 随机浮点数
     */
    randomFloat: function(min, max) {
        return Math.random() * (max - min) + min;
    },
    
    /**
     * 范围约束
     */
    clamp: function(value, min, max) {
        return Math.min(Math.max(value, min), max);
    },
    
    /**
     * 线性插值
     */
    lerp: function(start, end, factor) {
        return start + (end - start) * factor;
    },
    
    /**
     * 百分比
     */
    percent: function(value, total) {
        return total === 0 ? 0 : (value / total) * 100;
    }
};

/**
 * 颜色工具类
 */
const ColorUtils = {
    /**
     * 生成随机颜色
     */
    random: function() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    },
    
    /**
     * 颜色亮度
     */
    brightness: function(color) {
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        return (r * 299 + g * 587 + b * 114) / 1000;
    },
    
    /**
     * 文字颜色
     */
    textColor: function(bgColor) {
        return this.brightness(bgColor) > 128 ? '#000000' : '#ffffff';
    }
};

/**
 * 导出工具类
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        Encoder,
        Validator,
        Network,
        StringUtils,
        ArrayUtils,
        MathUtils,
        ColorUtils
    };
} else {
    // 浏览器环境，添加到全局
    window.Encoder = Encoder;
    window.Validator = Validator;
    window.Network = Network;
    window.StringUtils = StringUtils;
    window.ArrayUtils = ArrayUtils;
    window.MathUtils = MathUtils;
    window.ColorUtils = ColorUtils;
}


function safeRedirect(url) {
    try {
        // 检查是否已经在目标页面
        if (window.location.pathname !== new URL(url, window.location.origin).pathname) {
            window.location.href = url;
        }
    } catch (e) {
        console.error('重定向失败:', e);
    }
}

// 导出到全局
window.safeRedirect = safeRedirect;