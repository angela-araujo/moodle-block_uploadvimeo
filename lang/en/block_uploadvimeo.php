<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_uploadvimeo', language 'en',
 * 
 *  
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// General.
$string['pluginname'] = 'Envio de Vídeos - Vimeo';
$string['uploadbutton'] = 'Meus vídeos';
$string['textblock'] = 'Para enviar e gerenciar seus vídeos, clique abaixo.';

// Settings - Access.
$string['config_headingaccess'] = 'Acesso Vimeo';
$string['config_clientid'] = 'Client ID';
$string['config_clientid_desc'] = '<b>Client ID</b> para a conta do Vimeo';
$string['config_clientsecret'] = 'Client Secret';
$string['config_clientsecret_desc'] = '<b>Client Secret</b> para a conta do Vimeo';
$string['config_accesstoken'] = 'Access Token';
$string['config_accesstoken_desc'] = '<b>Access Token</b> para a conta do Vimeo';

// List options for settings.
$string['hide'] = 'Ocultar';
$string['show'] = 'Mostrar';
$string['user'] = 'Usuário';
$string['anybody'] = 'Qualquer pessoa';
$string['contacts'] = 'Contatos';
$string['nobody'] = 'Ninguém';
$string['disable'] = 'Desativar';
$string['password'] = 'Senha';
$string['unlisted'] = 'Não listado';
$string['users'] = 'Usuários';
$string['private'] = 'Privado';
$string['public'] = 'Público';
$string['whitelist'] = 'Lista de permissões';

// Settings - Embed.
$string['config_headingembed'] = 'Incorporação no player';
$string['config_embedbuttonsembed'] = 'Exibir botão de incorporação';
$string['config_embedbuttonsembed_desc'] = 'Acesso ao código de incorporação no player';
$string['config_embedbuttonsfullscreen'] = 'Exibir botão de tela cheia';
$string['config_embedbuttonsfullscreen_desc'] = 'Acesso à opção de tela cheia no player';
$string['config_embedbuttonslike'] = 'Eibir botão de curtir';
$string['config_embedbuttonslike_desc'] = 'Acesso ao botão curtir no player';
$string['config_embedbuttonsshare'] = 'Exibir botão de compartilhar';
$string['config_embedbuttonsshare_desc'] = 'Acesso ao botão de compartilhar no player';
$string['config_embedcolor'] = 'Cor da interface do player';
$string['config_embedcolor_desc'] = 'Cor da interface do player';
$string['config_embedlogoscustomactive'] = 'Exibir o logo da conta no player';
$string['config_embedlogoscustomactive_desc'] = 'Exibir o logo da conta no player';
$string['config_embedlogosvimeo'] = 'Exibir o logo do Vimeo no player';
$string['config_embedlogosvimeo_desc'] = 'Exibir o logo do Vimeo no player';
$string['config_embedtitlename'] = 'Exibir o título do vídeo no player';
$string['config_embedtitlename_desc'] = 'Como lidar com o título do vídeo na barra de título do player incorporável. Descrições das opções:<br>
 * ocultar - oculta o título do vídeo.<br>
 * mostrar - Mostra o título do vídeo.<br>
 * usuário - permite que o usuário decida.';
$string['config_embedtitleportrait'] = 'Exibir a imagem da conta no player';
$string['config_width'] = 'Largura do vídeo padrão para incorporação';
$string['config_height'] = 'Altura do vídeo padrão para incorporação';


// Settings - Privacy.
$string['config_headingprivacy'] = 'Privacidade';
$string['config_privacyadd'] = 'Permitir adicionar às coleções do público';
$string['config_privacycomments'] = 'Permitir comentários';
$string['config_privacycomments_desc'] = 'The privacy level required to comment on the video. Option descriptions:<br>
Qualquer - Anyone can comment on the video.<br>
Contatos - Only the owner\'s contacts can comment on the video.<br>
Ninguém - No one can comment on the video.';
$string['config_privacydownload'] = 'Permitir download dos vídeos';
$string['config_privacyembed'] = 'Definir privacidade da incorporação';
$string['config_privacyembed_desc'] = 'A configuração de incorporação do vídeo. Especifique o valor da lista de permissões para restringir a incorporação a um conjunto específico de domínios.<br>
Para mais informações, consulte o guia Interagindo com vídeos. <br>
Descrições das opções:<br>
 * privado - O vídeo não pode ser incorporado.<br>
 * público - O vídeo pode ser incorporado.<br>
 * lista de permissões - o vídeo pode ser incorporado apenas nos domínios especificados.';
$string['config_privacyview'] = 'Definir privacidade do vídeo';

// Settings - Restrictions.
$string['config_headingrestriction'] = 'Restrição';
$string['config_whitelist'] = 'Domínios a serem considerados na whitelist de privacidade';

// Page form.
$string['titlepageblock'] = 'Enviar vídeos para a plataforma Vimeo';
$string['instructions'] = 'Para enviar um vídeo, arraste-o para dentro desta área demarcada, ou clique no botão <strong>Enviar vídeo</strong>.';
$string['btnsendvideo'] = 'Enviar Vídeo';
$string['msgalertvimeo'] = '<strong>Atenção:</strong> Ao enviar vídeos, você deve respeitar as regras do Vimeo, carregando apenas vídeos feitos por você que não tenham conteúdo impróprio. Para maiores informações, <a href="https://vimeo.com/help/guidelines#video" target="_blank">clique aqui</a>.';
$string['msgvideosuccess'] = 'Vídeo enviado com sucesso!';
$string['msguploadvimeo'] = '<b>ATENÇÃO</b>: o vídeo precisa ser processado pelos servidores do Vimeo e pode levar alguns minutos para poder ser exibido no seu conteúdo.';

$string['titlemyvideos'] = 'Meus vídeos';
$string['text_line1'] = 'Aqui são apresentados os vídeos que já foram enviados por você para a plataforma Vimeo. ';
$string['text_line2_with_video'] = 'Clique no vídeo desejado para obter seu código de incorporação e poder incluí-lo no seu conteúdo.';
$string['text_line2_empty'] = 'No momento, não há vídeos disponíveis.';

$string['edittitlevideo'] = 'Editar título do vídeo';
$string['showcodeembed'] = 'Mostrar código para embutir vídeo';
$string['playvideo'] = 'Tocar vídeo';
$string['editthumbnailvideo'] = 'Editar imagem do vídeo';
$string['deletevideo'] = 'Excluir vídeo definitivamente';

$string['tabshortcut'] = 'Atalho &nbsp;&nbsp;';
$string['tabhtml'] = 'Código HTML &nbsp;&nbsp;';
$string['btncopy'] = 'copiar';

$string['titlethumbnail'] = 'Alterar imagem do vídeo: ';
$string['instructionsthumbnail'] = 'Clique no botão <strong>Selecionar imagem</strong>.';
$string['instructionstypesthumbnail'] = 'Arquivos aceitos: JPEG, PNG, or GIF';
$string['btnthumbnail'] = 'Selecionar Imagem';

$string['titledelete'] = 'Apagar vídeo do Vimeo: ';
$string['msginstructionsdelete'] = 'Após apagado, o vídeo não poderá ser recuperado (não há backup).';
$string['msgconfirmdelete'] = 'Confirmo que o vídeo acima pode ser apagado do Vimeo.';
$string['btndelete'] = 'Apagar vídeo';

// Capability strings.
$string['uploadvimeo:myaddinstance'] = 'Adicionar bloco Envio de Vídeos - Vimeo';
$string['uploadvimeo:addinstance'] = 'Adicionar bloco Envio de Vídeos - Vimeo';
$string['uploadvimeo:seepagevideos'] = 'Ver página de Envio de vídeos - Vimeo';

// Shortcodes.
$string['shortcode:vimevideo'] = 'Shortcode para embedar vídeo vimeo';

// Log.
$string['event_video_uploaded'] = 'Video uploaded';
$string['event_video_edit_title'] = 'Título do vídeo editado';
$string['event_video_deleted'] = 'Vídeo excluído';

// Error
$string['msg_error_vimeo'] = 'Ocorreu um erro no Vimeo. Tente novamente mais tarde';
$string['msg_error_not_found_video'] = 'Video não encontrado {$a}';
$string['msg_error_not_update_video'] = 'Não foi possível atualizar o vídeo {$a}';
$string['msg_error_not_create_folder'] = 'Não foi possível criar a pasta (folder) {$a}';
$string['msg_error_not_move_video_folder'] = 'Não foi possível mover o vídeo {$a->videoid} para a pasta {$a->foldername}';
