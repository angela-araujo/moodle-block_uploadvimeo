# moodle-block_uploadvimeo
Block to upload videos to vimeo

This block was created to make it easy to upload videos to an institutional Vimeo account.

It works for roles with editing permissions in the course (such as teachers, administrators, etc).
Videos are organized by folders on Vimeo for each user.

It is necessary to create an app in the Vimeo institutional account.

### Scheduled tasks

Detail                               | Script
-------------------------------------|-----------------------------------------------
Carrega videos para o banco de dados |`\block_uploadvimeo\task\sync`
Atualizar imagens dos vídeos (recem-carregadas)         |`\block_uploadvimeo\task\updateimage`
Exclui do Zoom os vídeos concluídos no Vimeo|`\block_uploadvimeo\task\zoom_delete`
Upload videos from Zoom to Vimeo     |`\block_uploadvimeo\task\zoom_full`

### Scripts (Cli)

Detail                            | Script
----------------------------------|------------------------------
Execute update videos in Moodle by folder name and/or account id| `php blocks/uploadvimeo/cli/script.php --verbose --script=update --foldername=MoodleUpload_f0000 --accountid=99`
 | | `php blocks/uploadvimeo/cli/script.php -v -s=update -f=MoodleUpload_f0000 -a=99`


