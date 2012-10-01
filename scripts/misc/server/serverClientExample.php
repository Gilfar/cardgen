<?
////////////////////////////////////////////////////////////////////////
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////
$port = 52056;
echo "Connecting to server on port: $port\n";
$socket = stream_socket_client('tcp://127.0.0.1:' . $port, $errorNumber, $errorMessage);
if (!$socket) exit("Unable to open socket: $errorMessage ($errorNumber)");
echo 'Connected to server version: ' . fread($socket, 4096);
fwrite($socket, "echo \"Hello!\n\";");
echo "Message sent.\n";
fread($socket, 4096); // Wait for response.
echo "Confirmation received.\n";
echo "Shutdown sent.\n";
fwrite($socket, 'shutdownServer();');
fread($socket, 4096); // Wait for response.
fclose($socket);
echo "Shutdown complete.\n";

?>
