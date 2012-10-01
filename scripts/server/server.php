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
require_once 'scripts/includes/global.php';

echo "Card Generator v$version - Server\n\n";

$writer = new ImageWriter();

$port = 52056;
$serverSocket = stream_socket_server('tcp://0.0.0.0:' . $port, $errorNumber, $errorMessage);
if (!$serverSocket) error("Unable to open server socket: $errorMessage ($errorNumber)");
$sockets[] = $serverSocket;
echo "Server started on port: $port\n";
while ($sockets) {
	$readySockets = $sockets;
	if (stream_select($readySockets, $writeSockets = null, $exSockets = null, 5) === FALSE) break;
	for ($i = 0, $n = count($readySockets); $i < $n; ++$i) {
		$socket = $readySockets[$i];
		if ($socket === $serverSocket) {
			// Accept new connection.
			$socket = stream_socket_accept($serverSocket);
			if (stream_socket_get_name($socket, false) != "127.0.0.1:$port") {
				// Only accept localhost connections.
				fclose($socket);
				continue;
			}
			fwrite($socket, $version);
			echo "Client connected.\n";
			$sockets[] = $socket;
			continue;
		}
		$data = fread($socket, 4096);
		if (strlen($data) === 0) {
			// Connection closed.
			echo "Client disconnected.\n";
			fclose($socket);
			unset($sockets[array_search($socket, $sockets, true)]);
		} else if ($data === false) {
			// Unknown error occured.
			echo "Unknown error occured reading client socket.\n";
			unset($sockets[array_search($socket, $sockets, true)]);
		} else {
			// Received message.
			echo "Received: $data\n";
			eval($data);
			if ($sockets) fwrite($socket, 'Complete.');
		}
	}
}

function shutdownServer () {
	global $sockets;
	foreach ($sockets as $socket)
		fclose($socket);
	echo "Server shutdown.\n";
	$sockets = null;
}

?>
