function createWords(){
    const letters = 'abcdefg0123456789!'.split('');

    letters.forEach((letter, index) => {

        const size = Math.random() * 10 + 5;
        const left = Math.random() * (window.innerWidth - size);
        const top = Math.random() * (window.innerHeight - size);

        const alphabet = document.createElement('div');
        alphabet.textContent = letter;
        alphabet.classList.add('letter');
        alphabet.style.width = `${size}px`;
        alphabet.style.height = `${size}px`;
        alphabet.style.left = `${left}px`;
        alphabet.style.top = `${top}px`;
        alphabet.style.animationDuration = Math.random() * 3 + 2 + 's'; // 动画持续时间2-5秒

        document.body.appendChild(alphabet);

        // 动画结束后移除粒子
        alphabet.addEventListener('animationend', function() {
            alphabet.remove();
        });

    });

}

// 创建粒子
function initParticleEffectWord() {
    for (let i = 0; i < 50; i++) {
        createWords();
    }
}
// 确保DOM完全加载后再初始化粒子效果
document.addEventListener('DOMContentLoaded', initParticleEffectWord);

// document.addEventListener('DOMContentLoaded', () => {
//     const alphabet = document.getElementById('alphabet');
//     if (!alphabet) return; // 如果没有找到元素，提前退出函数
//
//     const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
//
//     letters.forEach((letter, index) => {
//         const size = Math.random() * 10 + 5;
//         const left = Math.random() * (window.innerWidth - size);
//         const top = Math.random() * (window.innerHeight - size);
//
//         const letterElement = document.createElement('div');
//         letterElement.textContent = letter;
//         letterElement.classList.add('letter');
//         letterElement.style.width = `${size}px`;
//         letterElement.style.height = `${size}px`;
//         letterElement.style.left = `${left}px`;
//         letterElement.style.top = `${top}px`;
//
//         // letterElement.style.left = `${index * (100 / letters.length)}%`;
//         letterElement.style.animationDuration = Math.random() * 3 + 2 + 's'; // 动画持续时间2-5秒
//         alphabet.appendChild(letterElement); // 确保alphabet不是null
//     });
// });