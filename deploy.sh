#!/bin/bash

##############################################
# 蓝莲花XSS在线平台 - 一键部署脚本
# 版本: 2.0.8
# 支持: Docker / Docker Compose
##############################################

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 打印带颜色的信息
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 打印横幅
print_banner() {
    echo -e "${GREEN}"
    cat << "EOF"
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║          蓝莲花 XSS 在线平台                               ║
║          Blue Lotus XSS Platform                           ║
║                                                            ║
║          Version: 2.0.8                                    ║
║          Website: https://xss.li                           ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# 检查命令是否存在
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 检查Docker环境
check_docker() {
    print_info "检查Docker环境..."
    
    if ! command_exists docker; then
        print_error "Docker 未安装！请先安装 Docker"
        print_info "安装指南: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose 未安装！请先安装 Docker Compose"
        print_info "安装指南: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    print_success "Docker 环境检查通过"
}

# 创建环境配置文件
setup_env() {
    print_info "配置环境变量..."
    
    if [ -f .env ]; then
        print_warning ".env 文件已存在"
        read -p "是否覆盖现有配置？(y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "保留现有配置"
            return
        fi
    fi
    
    cp .env.example .env
    
    # 生成随机密码
    DB_PASS=$(openssl rand -base64 16 | tr -d "=+/" | cut -c1-16)
    INSTALL_PASS=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-12)
    
    # 更新配置
    sed -i.bak "s/your_password_here/${DB_PASS}/" .env
    sed -i.bak "s/xss2024/${INSTALL_PASS}/" .env
    rm -f .env.bak
    
    print_success "环境配置文件创建完成"
    print_warning "数据库密码: ${DB_PASS}"
    print_warning "安装密码: ${INSTALL_PASS}"
    print_info "请妥善保管以上密码！"
}

# 创建必要的目录
create_directories() {
    print_info "创建必要的目录..."
    
    mkdir -p data/backups
    mkdir -p myjs
    mkdir -p jstemplates
    
    # 设置权限
    chmod -R 755 data myjs jstemplates
    
    print_success "目录创建完成"
}

# 构建和启动Docker容器
start_containers() {
    print_info "构建并启动Docker容器..."
    
    # 使用docker compose或docker-compose
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    else
        COMPOSE_CMD="docker-compose"
    fi
    
    # 停止旧容器（如果存在）
    $COMPOSE_CMD down 2>/dev/null || true
    
    # 构建镜像
    print_info "构建Docker镜像（可能需要几分钟）..."
    $COMPOSE_CMD build
    
    # 启动容器
    print_info "启动容器..."
    $COMPOSE_CMD up -d
    
    print_success "容器启动成功"
}

# 等待服务就绪
wait_for_services() {
    print_info "等待服务启动..."
    
    # 等待MySQL就绪
    max_attempts=30
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if docker exec xss_mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
            print_success "MySQL 已就绪"
            break
        fi
        attempt=$((attempt + 1))
        echo -n "."
        sleep 2
    done
    echo
    
    if [ $attempt -eq $max_attempts ]; then
        print_error "MySQL 启动超时"
        exit 1
    fi
    
    # 等待Web服务就绪
    sleep 5
    print_success "所有服务已就绪"
}

# 显示访问信息
show_info() {
    echo
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}    部署完成！${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo
    echo -e "  🌐 访问地址: ${BLUE}http://localhost${NC}"
    echo -e "  🔐 默认账号: ${YELLOW}admin${NC}"
    echo -e "  🔑 默认密码: ${YELLOW}Admin@123${NC}"
    echo
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo
    echo "  📚 常用命令:"
    echo "    查看日志: docker-compose logs -f"
    echo "    停止服务: docker-compose stop"
    echo "    启动服务: docker-compose start"
    echo "    重启服务: docker-compose restart"
    echo "    删除服务: docker-compose down"
    echo
    echo "  🔧 管理工具:"
    echo "    phpMyAdmin: http://localhost:8080 (需要添加 --profile tools 参数启动)"
    echo
    echo -e "${YELLOW}  ⚠️  首次使用请访问 http://localhost/install.php 完成数据库初始化${NC}"
    echo
}

# 主函数
main() {
    print_banner
    
    # 检查是否在项目根目录
    if [ ! -f "docker-compose.yml" ]; then
        print_error "请在项目根目录运行此脚本"
        exit 1
    fi
    
    # 执行部署步骤
    check_docker
    setup_env
    create_directories
    start_containers
    wait_for_services
    show_info
    
    print_success "部署完成！"
}

# 运行主函数
main
