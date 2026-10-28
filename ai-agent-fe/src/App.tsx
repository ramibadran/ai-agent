import { useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { api } from './api'
import type { Conversation, Message } from './api'
import { createEcho } from './echo'
import './App.css'

const suggestions = ['What was Northstar Nexus revenue in 2024?', 'How many people does the company plan to hire in 2025?', 'What is OrbitFlow?', 'Is the company expected to be profitable in 2026?']

export default function App() {
  const [token, setToken] = useState(localStorage.getItem('ai_agent_token')), [conversations, setConversations] = useState<Conversation[]>([]), [active, setActive] = useState<Conversation | null>(null)
  const [message, setMessage] = useState(''), [error, setError] = useState(''), [loading, setLoading] = useState(false), [streamed, setStreamed] = useState('')
  const bottom = useRef<HTMLDivElement>(null)
  const load = async () => setConversations((await api.conversations()).data)
  useEffect(() => { if (token) void load().catch(e => setError(e.message)) }, [token])
  useEffect(() => {
    bottom.current?.scrollIntoView({ behavior: 'smooth' })
  }, [active?.messages, loading])
  useEffect(() => {
    if (!active) return
    const echo = createEcho()
    echo.private(`conversation.${active.id}`).listen('.assistant.delta', (event: { delta: string }) => setStreamed(current => current + event.delta))
    return () => { echo.leave(`conversation.${active.id}`); echo.disconnect() }
  }, [active?.id])
  const login = async (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); setLoading(true); setError(''); try { const form = new FormData(event.currentTarget); const r = await api.login(String(form.get('email')), String(form.get('password'))); localStorage.setItem('ai_agent_token', r.authorization!.token); setToken(r.authorization!.token) } catch (e) { setError((e as Error).message) } finally { setLoading(false) } }
  const sendQuestion = async (question: string) => { if (!question.trim() || loading) return; setMessage(''); setError(''); setStreamed(''); setLoading(true); try { if (!active) { const r = await api.createConversation(question); setActive(r.data) } else { const r = await api.sendMessage(active.id, question); setActive({ ...active, messages: [...(active.messages ?? []), r.data.user_message, r.data.assistant_message] }) }; await load() } catch (e) { setError((e as Error).message) } finally { setLoading(false); setStreamed('') } }
  const send = (event: FormEvent) => { event.preventDefault(); void sendQuestion(message.trim()) }
  const remove = async (id: number) => { if (!confirm('Delete this conversation and its message history?')) return; try { await api.deleteConversation(id); if (active?.id === id) setActive(null); await load() } catch (e) { setError((e as Error).message) } }
  if (!token) return <main className="login"><form onSubmit={login}><b>✦ AI KNOWLEDGE AGENT</b><h1>Welcome back</h1><input name="email" defaultValue="admin@example.test" type="email" /><input name="password" defaultValue="password" type="password" /><button disabled={loading}>Sign in</button>{error && <small>{error}</small>}</form></main>
  return <main className="app"><aside><b>✦ Knowledge Agent</b><button className="new" onClick={() => { setActive(null); setError('') }}>＋ New chat</button><small>RECENT CONVERSATIONS</small>{conversations.map(c => <div className="conversation" key={c.id}><button onClick={() => void api.conversation(c.id).then(r => setActive(r.data)).catch(e => setError(e.message))}>{c.title}</button><button aria-label="Delete conversation" onClick={() => void remove(c.id)}>×</button></div>)}<button className="signout" onClick={() => { localStorage.removeItem('ai_agent_token'); setToken(null); setActive(null) }}>Sign out</button></aside><section><header><div><small>RAG ASSISTANT</small><h2>{active?.title ?? 'New conversation'}</h2></div><span>● Knowledge base connected</span></header><div className="messages">{!active && <div className="empty"><h1>Ask your knowledge base</h1><p>Answers use indexed documents and show their sources.</p><div className="suggestions">{suggestions.map(q => <button key={q} onClick={() => void sendQuestion(q)}>{q}</button>)}</div></div>}{active?.messages?.map((item: Message) => <article className={item.role} key={item.id}><b>{item.role === 'user' ? 'You' : 'AI Assistant'}</b><p>{item.content}</p>{item.sources?.length ? <div className="sources"><small>Sources</small>{item.sources.map((s, i) => <a key={`${s.title}-${i}`} href={s.source_url ?? '#'} target="_blank" rel="noreferrer"><span>{s.title} · chunk {s.chunk_number}</span><em>{Math.round(s.score * 100)}% match</em></a>)}</div> : null}</article>)}{loading && <article className="assistant loading"><b>AI Assistant</b><p>{streamed || <><i /> Searching knowledge and preparing an answer…</>}</p></article>}<div ref={bottom} /></div><form className="composer" onSubmit={send}><input value={message} onChange={e => setMessage(e.target.value)} disabled={loading} placeholder="Ask a question about your knowledge…" /><button disabled={loading}>{loading ? 'Working…' : 'Send'}</button></form>{error && <p className="error" role="alert">{error}<button onClick={() => setError('')}>Dismiss</button></p>}</section></main>
}
