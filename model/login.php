
<?php
  // Inclui o arquivo de conexão com o banco de dados
  include ('conexao.php');
  // Verifica se os campos 'email' ou 'senha' foram enviados via POST
  if(isset($_POST['email']) || isset($_POST['senha'])){

    // Verifica se o campo 'email' está vazio
    if(strlen($_POST['email']) == 0){
      echo "Preencha seu e-mail";
    // Verifica se o campo 'senha' está vazio
    }else if(strlen($_POST['senha']) == 0){
      echo "Preencha sua senha";
    
    }else{
      // Escapa os dados para evitar SQL Injection
      $email = $mysqli->real_escape_string($_POST['email']);
      $senha = $mysqli->real_escape_string($_POST['senha']);

      // Consulta SQL para buscar o usuário com a matrícula e senha informadas
      $sql_code = "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha'";
      $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: ". $mysqli->error);

      // Verifica se foi encontrado algum usuário
      $quantidade = $sql_query->num_rows;

      if($quantidade == 1){
        // Obtém os dados do usuário
        $usuario = $sql_query->fetch_assoc();

        // Inicia a sessão, se ainda não tiver sido iniciada
        if(!isset($_SESSION)){
          session_start();
        }
        // Armazena informações do usuário na sessão
        $_SESSION['user'] = $usuario['id'];
        $_SESSION['name'] = $usuario['nome'];
        
        // Redireciona o usuário para a página principal após login
        header('Location: ../home.php');

      }else {
        // Caso matrícula ou senha estejam incorretos
        echo "Falha ao logar! Matricula ou senha incorretos";
      } 
  }
}
?> 