<?php
declare(strict_types=1);

class FinanceiroModel
{
    public function __construct(private PDO $pdo) {}

    public function coorte(): array
    {
        $c = $this->pdo->query('SELECT * FROM coortes ORDER BY id LIMIT 1')->fetch();
        if ($c) return $c;
        $this->pdo->prepare('INSERT INTO coortes (nome, criado_por) VALUES (?, ?)')->execute(['Formandos 2026', $_SESSION['usuario']['id'] ?? null]);
        $id = (int)$this->pdo->lastInsertId();
        $st = $this->pdo->prepare('INSERT INTO anos_letivos (coorte_id, ano_escolar, ano) VALUES (?, ?, ?)');
        $st->execute([$id, 2, 2025]); $st->execute([$id, 3, 2026]);
        foreach (['Mensalidade','Espaço e estrutura','Foto e vídeo','Eventos e ações','Outros','Receita extra'] as $cat) {
            $this->pdo->prepare('INSERT IGNORE INTO categorias_financeiras (coorte_id,nome) VALUES (?,?)')->execute([$id,$cat]);
        }
        return $this->pdo->query('SELECT * FROM coortes WHERE id=' . $id)->fetch();
    }

    public function resumo(int $coorteId): array
    {
        $st=$this->pdo->prepare("SELECT tipo, COALESCE(SUM(CASE WHEN estorno_de IS NULL THEN valor ELSE -valor END),0) total FROM movimentacoes WHERE coorte_id=? GROUP BY tipo");$st->execute([$coorteId]);
        $tot=['receita'=>0.0,'despesa'=>0.0];foreach($st->fetchAll() as $r)$tot[$r['tipo']]=(float)$r['total'];
        $st=$this->pdo->prepare("SELECT COUNT(*) total, SUM(status='ativo') ativos FROM participantes WHERE coorte_id=?");$st->execute([$coorteId]);$p=$st->fetch() ?: ['total'=>0,'ativos'=>0];
        return ['receitas'=>$tot['receita'],'despesas'=>$tot['despesa'],'saldo'=>$tot['receita']-$tot['despesa'],'participantes'=>(int)$p['total'],'ativos'=>(int)$p['ativos']];
    }

    public function evolucaoMensal(int $coorteId,int $meses=6): array
    {
        $fim=new DateTimeImmutable('first day of this month');$inicio=$fim->modify('-'.($meses-1).' months');
        $st=$this->pdo->prepare('SELECT DATE_FORMAT(competencia,\'%Y-%m-01\') mes,tipo,SUM(valor) total FROM movimentacoes WHERE coorte_id=? AND competencia>=? GROUP BY mes,tipo');
        $st->execute([$coorteId,$inicio->format('Y-m-d')]);$rows=$st->fetchAll();$map=[];
        foreach($rows as $r){$key=date('Y-m',strtotime($r['mes']));$map[$key][$r['tipo']]=(float)$r['total'];}
        $nomes=['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];$out=[];for($i=0;$i<$meses;$i++){$d=$inicio->modify("+$i months");$key=$d->format('Y-m');$out[]=['label'=>$nomes[(int)$d->format('n')-1],'receita'=>$map[$key]['receita']??0,'despesa'=>$map[$key]['despesa']??0];}return $out;
    }

    public function movimentacoes(int $coorteId, ?int $participanteId=null): array
    {
        $sql='SELECT m.*, c.nome categoria, p.nome participante FROM movimentacoes m LEFT JOIN categorias_financeiras c ON c.id=m.categoria_id LEFT JOIN participantes p ON p.id=m.participante_id WHERE m.coorte_id=?';
        $args=[$coorteId]; if($participanteId){$sql.=' AND m.participante_id=?';$args[]=$participanteId;} $sql.=' ORDER BY m.data_movimento DESC,m.id DESC';
        $st=$this->pdo->prepare($sql);$st->execute($args);return $st->fetchAll();
    }

    public function categorias(int $coorteId): array
    { $st=$this->pdo->prepare('SELECT id,nome FROM categorias_financeiras WHERE coorte_id=? ORDER BY nome');$st->execute([$coorteId]);return $st->fetchAll(); }

    public function despesasPorCategoria(int $coorteId): array
    { $st=$this->pdo->prepare("SELECT c.nome,COALESCE(SUM(m.valor),0) valor FROM categorias_financeiras c LEFT JOIN movimentacoes m ON m.categoria_id=c.id AND m.tipo='despesa' AND m.estorno_de IS NULL WHERE c.coorte_id=? GROUP BY c.id ORDER BY valor DESC");$st->execute([$coorteId]);return $st->fetchAll(); }

    public function participantesAtivos(int $coorteId): array
    { $st=$this->pdo->prepare("SELECT id,nome FROM participantes WHERE coorte_id=? AND status='ativo' ORDER BY nome");$st->execute([$coorteId]);return $st->fetchAll(); }

    public function parcelasAbertas(int $coorteId): array
    { $st=$this->pdo->prepare("SELECT pp.id,pp.valor,pp.competencia,p.nome,COALESCE((SELECT SUM(m.valor) FROM movimentacoes m WHERE m.parcela_id=pp.id AND m.estorno_de IS NULL),0) pago FROM parcelas_previstas pp JOIN participantes p ON p.id=pp.participante_id WHERE p.coorte_id=? AND pp.status IN ('aberta','parcial') ORDER BY pp.vencimento");$st->execute([$coorteId]);return $st->fetchAll(); }

    public function participanteDoUsuario(int $coorteId,int $usuarioId): ?array
    { $st=$this->pdo->prepare('SELECT * FROM participantes WHERE coorte_id=? AND usuario_id=? LIMIT 1');$st->execute([$coorteId,$usuarioId]);return $st->fetch()?:null; }

    public function parcelasParticipante(int $participanteId): array
    { $st=$this->pdo->prepare("SELECT pp.*,COALESCE((SELECT SUM(m.valor) FROM movimentacoes m WHERE m.parcela_id=pp.id AND m.estorno_de IS NULL),0) pago FROM parcelas_previstas pp WHERE pp.participante_id=? ORDER BY pp.competencia");$st->execute([$participanteId]);return $st->fetchAll(); }

    public function participantes(int $coorteId): array
    {
        $st=$this->pdo->prepare("SELECT p.*, COALESCE((SELECT SUM(m.valor) FROM movimentacoes m WHERE m.participante_id=p.id AND m.tipo='receita' AND m.estorno_de IS NULL),0) contribuido, COALESCE((SELECT SUM(x.valor) FROM parcelas_previstas x WHERE x.participante_id=p.id AND x.status<>'cancelada'),0) previsto FROM participantes p WHERE p.coorte_id=? ORDER BY p.nome");$st->execute([$coorteId]);return $st->fetchAll();
    }

    public function adicionarParticipante(int $coorteId,string $nome,?string $email,float $valor,int $usuarioId): void
    {
        $this->pdo->beginTransaction();try{
            $st=$this->pdo->prepare("INSERT INTO participantes (coorte_id,nome,email,status,entrou_em) VALUES (?,?,?,'ativo',CURDATE())");$st->execute([$coorteId,trim($nome),$email ?: null]);$pid=(int)$this->pdo->lastInsertId();
            $st=$this->pdo->prepare("INSERT INTO planos_contribuicao (coorte_id,valor,periodicidade,vigencia_inicio) VALUES (?,?,'mensal',CURDATE())");$st->execute([$coorteId,$valor]);$plan=(int)$this->pdo->lastInsertId();
            $st=$this->pdo->prepare('INSERT INTO parcelas_previstas (participante_id,plano_id,competencia,vencimento,valor) VALUES (?,?,?,?,?)');
            for($i=0;$i<11;$i++){$comp=new DateTimeImmutable('first day of next month');$comp=$comp->modify("+$i months");$due=$comp->setDate((int)$comp->format('Y'),(int)$comp->format('m'),10);$st->execute([$pid,$plan,$comp->format('Y-m-d'),$due->format('Y-m-d'),$valor]);}
            $this->pdo->commit();
        }catch(Throwable $e){$this->pdo->rollBack();throw $e;}
    }

    public function lancar(int $coorteId,int $usuarioId,string $tipo,string $descricao,float $valor,string $data,string $meio,int $categoria,?int $participante=null,?int $parcela=null): void
    {
        if(!in_array($tipo,['receita','despesa'],true)||$valor<=0)throw new InvalidArgumentException('Informe tipo e valor válido.');
        $this->pdo->beginTransaction();try{
            if($parcela){if($tipo!=='receita')throw new InvalidArgumentException('Parcelas só podem ser conciliadas com receitas.');$q=$this->pdo->prepare('SELECT valor,participante_id FROM parcelas_previstas WHERE id=? FOR UPDATE');$q->execute([$parcela]);$pr=$q->fetch();if(!$pr)throw new InvalidArgumentException('Parcela não encontrada.');$participante=(int)$pr['participante_id'];$q=$this->pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM movimentacoes WHERE parcela_id=? AND estorno_de IS NULL");$q->execute([$parcela]);if($valor>(float)$pr['valor']-(float)$q->fetchColumn()+0.001)throw new InvalidArgumentException('O valor ultrapassa o restante da parcela.');}
            $st=$this->pdo->prepare('INSERT INTO movimentacoes (coorte_id,parcela_id,participante_id,usuario_id,tipo,categoria_id,descricao,valor,data_movimento,competencia,meio) VALUES (?,?,?,?,?,?,?,?,?,?,?)');$st->execute([$coorteId,$parcela,$participante,$usuarioId,$tipo,$categoria,$descricao,$valor,$data,date('Y-m-01',strtotime($data)),$meio]);$mid=(int)$this->pdo->lastInsertId();
            if($parcela){$q=$this->pdo->prepare('SELECT valor FROM parcelas_previstas WHERE id=?');$q->execute([$parcela]);$due=(float)$q->fetchColumn();$q=$this->pdo->prepare("SELECT SUM(valor) FROM movimentacoes WHERE parcela_id=? AND estorno_de IS NULL");$q->execute([$parcela]);$paid=(float)$q->fetchColumn();$this->pdo->prepare('UPDATE parcelas_previstas SET status=? WHERE id=?')->execute([$paid+0.001 >= $due?'paga':'parcial',$parcela]);}
            $this->pdo->prepare('INSERT INTO historico_movimentacoes (movimentacao_id,usuario_id,acao) VALUES (?,?,?)')->execute([$mid,$usuarioId,'criado']);$this->pdo->commit();
        }catch(Throwable $e){$this->pdo->rollBack();throw $e;}
    }

    public function atualizarCoorte(int $id,string $nome,float $meta,int $dia): void
    { $this->pdo->prepare('UPDATE coortes SET nome=?,meta=?,vencimento_dia=? WHERE id=?')->execute([trim($nome),$meta,$dia,$id]); }
}
