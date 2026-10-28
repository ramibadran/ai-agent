import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { api } from './api'
import type { Conversation, Message } from './api'
import './App.css'

export default function App() {
  const [token, setToken] = useState(localStorage.getItem('ai_agent_token'))
  const [conversations, setConversations] = useState<Conversation[]>([]), [active, setActive] = useState<Conversation | null>(null)
  const [message, setMessage] = useState(''), [error, setError] = useState(''), [loading, setLoading] = useState(false)
  const load = async () => setConversations((await api.conversations()).data)
  useEffect(() => { if (token) void load().catch(e => setError(e.message)) }, [token])
  const login = async (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); setLoading(true); try { const form = new FormData(event.currentTarget); const result = await api.login(String(form.get('email')), String(form.get('password'))); localStorage.setItem('ai_agent_token', result.authorization!.token); setToken(result.authorization!.token) } catch (e) { setError((e as Error).message) } finally { setLoading(false) } }
  const send = async (event: FormEvent) => { event.preventDefault(); if (!message.trim() || loading) return; const question = message.trim(); setMessage(''); setLoading(true); try { if (!active) { const result = await api.createConversation(question); setActive(result.data) } else { const result = await api.sendMessage(active.id, question); setActive({ ...active, messages: [...(active.messages ?? []), result.data.user_message, result.data.assistant_message] }) }; await load() } catch (e) { setError((e as Error).message) } finally { setLoading(false) } }
  if (!token) return <main className="login"><form onSubmit={login}><b>✦ AI KNOWLEDGE AGENT</b><h1>Welcome back</h1><input name="email" defaultValue="admin@example.test" type="email" /><input name="password" defaultValue="password" type="password" /><button disabled={loading}>Sign in</button>{error && <small>{error}</small>}</form></main>
  return <main className="app"><aside><b>✦ Knowledge Agent</b><button onClick={() => setActive(null)}>＋ New chat</button><small>RECENT CONVERSATIONS</small>{conversations.map(c => <button key={c.id} onClick={() => void api.conversation(c.id).then(r => setActive(r.data))}>{c.title}</button>)}<button onClick={() => { localStorage.removeItem('ai_agent_token'); setToken(null) }}>Sign out</button></aside><section><header><div><small>RAG ASSISTANT</small><h2>{active?.title ?? 'New conversation'}</h2></div><span>● Knowledge base connected</span></header><div className="messages">{!active && <div className="empty"><h1>Ask your knowledge base</h1><p>Answers include their retrieved sources.</p></div>}{active?.messages?.map((item: Message) => <article className={item.role} key={item.id}><b>{item.role === 'user' ? 'You' : 'AI Assistant'}</b><p>{item.content}</p>{item.sources?.length ? <small>Sources: {item.sources.map(s => s.title).join(', ')}</small> : null}</article>)}{loading && <article className="assistant">AI Assistant · Thinking…</article>}</div><form className="composer" onSubmit={send}><input value={message} onChange={e => setMessage(e.target.value)} placeholder="Ask a question about your knowledge…" /><button>Send</button></form>{error && <p className="error">{error}</p>}</section></main>
}
