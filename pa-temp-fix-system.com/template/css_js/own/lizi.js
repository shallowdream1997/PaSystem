function createParticle() {
    const size = Math.random() * 10 + 5;
    const left = Math.random() * (window.innerWidth - size);
    const top = Math.random() * (window.innerHeight - size);

    const particle = document.createElement('div');
    particle.classList.add('particle');
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    particle.style.left = `${left}px`;
    particle.style.top = `${top}px`;
    particle.style.animationDuration = Math.random() * 3 + 2 + 's'; // 动画持续时间2-5秒

    document.body.appendChild(particle);

    // 动画结束后移除粒子
    particle.addEventListener('animationend', function() {
        particle.remove();
    });
}

// 创建粒子
function initParticleEffect() {
    for (let i = 0; i < 50; i++) {
        createParticle();
    }
}


// 确保DOM完全加载后再初始化粒子效果
document.addEventListener('DOMContentLoaded', initParticleEffect);


/**
 * 获取当前项目的ip地址+端口
 * @returns {string}
 */
function getDomainAndPort() {
    const url = window.location;
    const domainName = url.hostname;
    const port = url.port; // 端口号
    return `http://${domainName}:${port}`;
}