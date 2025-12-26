import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class HiotakuChatService {
  static const String _apiUrl = String.fromEnvironment('API_URL');
  static const String _token = String.fromEnvironment('API_TOKEN');
  
  static Future<Map<String, dynamic>> sendMessage(String message, String userUuid) async {
    try {
      final response = await http.post(
        Uri.parse('$_apiUrl/botchain/api.php?token=$_token'),
        headers: {
          'Content-Type': 'application/json',
          'X-User-UUID': userUuid,
        },
        body: json.encode({'message': message}),
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        throw Exception('Failed to send message: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
}

class ChatScreen extends StatefulWidget {
  final String userUuid;
  
  const ChatScreen({Key? key, required this.userUuid}) : super(key: key);
  
  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _controller = TextEditingController();
  final List<Map<String, dynamic>> _messages = [];
  bool _isLoading = false;
  
  void _sendMessage() async {
    if (_controller.text.trim().isEmpty) return;
    
    final userMessage = _controller.text.trim();
    _controller.clear();
    
    setState(() {
      _messages.add({
        'text': userMessage,
        'isUser': true,
        'timestamp': DateTime.now(),
      });
      _isLoading = true;
    });
    
    try {
      final response = await HiotakuChatService.sendMessage(userMessage, widget.userUuid);
      
      setState(() {
        _messages.add({
          'text': response['response'],
          'isUser': false,
          'timestamp': DateTime.now(),
          'userName': response['user_name'],
        });
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _messages.add({
          'text': 'Error: $e',
          'isUser': false,
          'timestamp': DateTime.now(),
        });
        _isLoading = false;
      });
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Hisu AI Chat'),
        backgroundColor: Colors.orange,
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              padding: EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                return ChatBubble(
                  text: message['text'],
                  isUser: message['isUser'],
                  timestamp: message['timestamp'],
                );
              },
            ),
          ),
          if (_isLoading)
            Padding(
              padding: EdgeInsets.all(8),
              child: Row(
                children: [
                  CircularProgressIndicator(color: Colors.orange),
                  SizedBox(width: 8),
                  Text('Hisu is typing...'),
                ],
              ),
            ),
          Container(
            padding: EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: InputDecoration(
                      hintText: 'Type your message...',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(25),
                      ),
                    ),
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
                SizedBox(width: 8),
                FloatingActionButton(
                  onPressed: _sendMessage,
                  backgroundColor: Colors.orange,
                  child: Icon(Icons.send),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class ChatBubble extends StatelessWidget {
  final String text;
  final bool isUser;
  final DateTime timestamp;
  
  const ChatBubble({
    Key? key,
    required this.text,
    required this.isUser,
    required this.timestamp,
  }) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: EdgeInsets.symmetric(vertical: 4),
        padding: EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isUser ? Colors.orange : Colors.grey[300],
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              text,
              style: TextStyle(
                color: isUser ? Colors.white : Colors.black,
                fontSize: 16,
              ),
            ),
            SizedBox(height: 4),
            Text(
              '${timestamp.hour}:${timestamp.minute.toString().padLeft(2, '0')}',
              style: TextStyle(
                color: isUser ? Colors.white70 : Colors.grey[600],
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
