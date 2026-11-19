function alternarModoBusca() {
    const select = document.getElementById('metodo');
    const painel = document.getElementById('painel-procedure');
    
    if (select.value === 'procedure') {
        painel.classList.add('ativo');
    } else {
        painel.classList.remove('ativo');
        // Volta imediatamente para a View
        window.location.href = '?metodo=view';
    }
}

function atualizarTextoSlider(valor) {
    const formatado = parseFloat(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    document.getElementById('valor-slider').textContent = formatado;
}

function enviarFiltroAuto() {
    document.getElementById('form-filtro').submit();
}

function abrirModalLivro(id, titulo, autor, preco, quantidade) {
    document.getElementById('modal-id-livro').value = id;
    document.getElementById('modal-titulo').textContent = titulo;
    document.getElementById('modal-autor-nome').textContent = autor;
    document.getElementById('modal-input-preco').value = preco;
    document.getElementById('modal-input-quantidade').value = quantidade;

    document.getElementById('area-historico').classList.remove('visivel');

    const tbody = document.getElementById('corpo-tabela-historico');
    tbody.innerHTML = '';

    const registros = (typeof historicoAuditoria !== 'undefined' && historicoAuditoria[id]) ? historicoAuditoria[id] : [];

    if (registros.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="sem-registro">Nenhuma alteração registrada para este livro.</td></tr>';
    } else {
        registros.forEach(reg => {
            const pAntigo = parseFloat(reg.preco_antigo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            const pNovo = parseFloat(reg.preco_novo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            const precoMudou = reg.preco_antigo !== reg.preco_novo;

            const qAntiga = parseInt(reg.quantidade_antiga, 10);
            const qNova = parseInt(reg.quantidade_nova, 10);
            const qtdMudou = qAntiga !== qNova;
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${reg.data_formatada}</td>
                <td>${pAntigo} → <span class="${precoMudou ? 'destaque-mudanca' : ''}">${pNovo}</span></td>
                <td>${qAntiga} un. → <span class="${qtdMudou ? 'destaque-mudanca' : ''}">${qNova} un.</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('modal-livro').classList.add('ativo');
}

function fecharModal() {
    document.getElementById('modal-livro').classList.remove('ativo');
}

function fecharModalFora(event) {
    if (event.target.id === 'modal-livro') {
        fecharModal();
    }
}

function alternarHistorico() {
    document.getElementById('area-historico').classList.toggle('visivel');
}
